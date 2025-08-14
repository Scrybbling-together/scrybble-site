<?php

namespace App\modules\CryptFS\Services;

use App\Exceptions\CryptFSException;
use App\Helpers\FileManipulations;
use App\Helpers\UserStorage;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class CryptFSMountingService
{
    private const int MOUNT_TIMEOUT = 30;
    private const int UNMOUNT_TIMEOUT = 10;
    private const int INIT_TIMEOUT = 30;


    /**
     * @throws CryptFSException
     */
    public function mountUserFolder(User $user, string $key): bool
    {
        $userId = $user->id;
        $encryptedPath = $this->getEncryptedPath($userId);
        $decryptedPath = $this->getDecryptedPath($userId);

        if ($this->isUserFolderMounted($user->id)) {
            return true;
        }

        $this->ensureDirectoriesExist($encryptedPath, $decryptedPath);

        if (!$this->isEncryptedFolderInitialized($encryptedPath)) {
            $this->initializeEncryptedFolder($encryptedPath, $key);
        }

        $timeout = config('scrybble.cryptfs.mount_timeout', self::MOUNT_TIMEOUT);
        $result = Process::timeout($timeout)->run([
            'gocryptfs', $encryptedPath, '-allow_other', '-extpass', 'echo', '-extpass', $key, $decryptedPath
        ]);

        if ($result->successful()) {
            Log::info("Successfully mounted encrypted folder for user $userId");
            $this->migrateLegacyStorage($user);
            return true;
        }

        Log::error("Failed to mount folder for user $userId: " . $result->errorOutput());
        throw new CryptFSException("Failed to mount encrypted folder: " . $result->errorOutput());
    }

    public function unmountUserFolder(User $user): bool
    {
        $userId = $user->id;
        $decryptedPath = $this->getDecryptedPath($userId);

        if (!$this->isUserFolderMounted($userId)) {
            return true;
        }

        $result = Process::timeout(self::UNMOUNT_TIMEOUT)->run([
            'fusermount', '-u', $decryptedPath
        ]);

        if ($result->successful()) {
            Log::info("Successfully unmounted folder for user $userId");
            return true;
        }

        Log::error("Failed to unmount folder for user $userId: " . $result->errorOutput());
        return false;
    }

    public function isUserFolderMounted(int $user_id): bool
    {
        $decryptedPath = $this->getDecryptedPath($user_id);

        // In testing environment, we can't actually mount gocryptfs
        // so we just check if the directory exists
        if (app()->environment('testing')) {
            return is_dir($decryptedPath);
        }

        return is_dir($decryptedPath) && $this->isMountPoint($decryptedPath);
    }

    public function getAllMountedFolders(): array
    {
        $decryptedBasePath = config('scrybble.cryptfs.decrypted_path');
        if (!is_dir($decryptedBasePath)) {
            return [];
        }

        $mountedFolders = [];
        $folders = glob($decryptedBasePath . '/user-*', GLOB_ONLYDIR);

        foreach ($folders as $folder) {
            if ($this->isMountPoint($folder)) {
                preg_match('/user-(\d+)$/', $folder, $matches);
                if (isset($matches[1])) {
                    $mountedFolders[] = (int) $matches[1];
                }
            }
        }

        return $mountedFolders;
    }

    private function getEncryptedPath(int $userId): string
    {
        return config('scrybble.cryptfs.encrypted_path') . "/user-$userId";
    }

    private function getDecryptedPath(int $userId): string
    {
        return config('scrybble.cryptfs.decrypted_path') . "/user-$userId";
    }

    private function ensureDirectoriesExist(string $encryptedPath, string $decryptedPath): void
    {
        if (!is_dir($encryptedPath)) {
            mkdir($encryptedPath, 0700, true);
        }
        if (!is_dir($decryptedPath)) {
            mkdir($decryptedPath, 0700, true);
        }
    }

    private function isEncryptedFolderInitialized(string $encryptedPath): bool
    {
        return file_exists($encryptedPath . '/gocryptfs.conf');
    }

    /**
     * @throws CryptFSException
     */
    private function initializeEncryptedFolder(string $encryptedPath, string $key): void
    {
        $result = Process::timeout(self::INIT_TIMEOUT)->run([
            'gocryptfs', '-init', $encryptedPath, '-extpass', 'echo', '-extpass', $key
        ]);

        if (!$result->successful()) {
            throw new CryptFSException("Failed to initialize encrypted folder: " . $result->errorOutput());
        }

        Log::info("Initialized new encrypted folder at $encryptedPath");
    }

    private function isMountPoint(string $path): bool
    {
        $result = Process::run(['mountpoint', '-q', $path]);
        return $result->successful();
    }

    private function migrateLegacyStorage(User $user): void
    {
        $userId = $user->id;
        $efs = Storage::disk('efs');
        $legacyUserDir = "user-$userId";

        if (!$efs->exists($legacyUserDir)) {
            return;
        }

        if (!$this->isUserFolderMounted($user->id)) {
            Log::info("Skipping migration for user $userId - encrypted folder not mounted");
            return;
        }

        Log::info("Starting migration of legacy storage for user $userId");

        try {
            $decryptedStorage = UserStorage::get($user);

            FileManipulations::moveFilesRecursively($efs, $legacyUserDir, $decryptedStorage, '');

            if (FileManipulations::verifyFilesMatch($efs, $legacyUserDir, $decryptedStorage, '')) {
                $efs->deleteDirectory($legacyUserDir);
                Log::info("Successfully migrated and cleaned up legacy storage for user $userId");
            } else {
                Log::error("Migration verification failed for user $userId");
            }
            return;
        } catch (Exception $e) {
            Log::error("Migration failed for user $userId: " . $e->getMessage());
        }

    }
}
