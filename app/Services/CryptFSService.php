<?php

namespace App\Services;

use App\Models\User;
use App\Exceptions\CryptFSException;
use App\Helpers\FileManipulations;
use App\Helpers\UserStorage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CryptFSService
{
    private const int MOUNT_TIMEOUT = 30; // seconds (fallback)
    private const int SESSION_TTL = 600; // 10 minutes (fallback)

    /**
     * @throws CryptFSException
     */
    public function mountUserFolder(User $user, string $key): bool
    {
        $userId = $user->id;
        $encryptedPath = config('scrybble.cryptfs.encrypted_path') . "/user-{$userId}";
        $decryptedPath = config('scrybble.cryptfs.decrypted_path') . "/user-{$userId}";

        // Check if already mounted
        if ($this->isUserFolderMounted($user)) {
            $this->updateSession($user);
            return true;
        }

        // Ensure directories exist
        $this->ensureDirectoriesExist($encryptedPath, $decryptedPath);

        // Initialize encrypted folder if it doesn't exist
        if (!$this->isEncryptedFolderInitialized($encryptedPath)) {
            $this->initializeEncryptedFolder($encryptedPath, $key);
        }

        // Mount the folder
        $timeout = config('scrybble.cryptfs.mount_timeout', self::MOUNT_TIMEOUT);
        $result = Process::timeout($timeout)->run([
            'gocryptfs', $encryptedPath, '-allow_other', '-extpass', 'echo', '-extpass', $key, $decryptedPath
        ]);

        if ($result->successful()) {
            $this->createSession($user);
            Log::info("Successfully mounted encrypted folder for user {$userId}");

            $this->migrateLegacyStorage($user);

            return true;
        }

        Log::error("Failed to mount folder for user {$userId}: " . $result->errorOutput());
        throw new CryptFSException("Failed to mount encrypted folder: " . $result->errorOutput());
    }

    public function unmountUserFolder(User $user): bool
    {
        $userId = $user->id;
        $decryptedPath = config('scrybble.cryptfs.decrypted_path') . "/user-{$userId}";

        if (!$this->isUserFolderMounted($user)) {
            return true; // Already unmounted
        }

        // Unmount using fusermount
        $result = Process::timeout(10)->run([
            'fusermount', '-u', $decryptedPath
        ]);

        if ($result->successful()) {
            $this->destroySession($user);
            Log::info("Successfully unmounted folder for user {$userId}");
            return true;
        }

        Log::error("Failed to unmount folder for user {$userId}: " . $result->errorOutput());
        return false;
    }

    public function isUserFolderMounted(User $user): bool
    {
        $decryptedPath = config('scrybble.cryptfs.decrypted_path') . "/user-{$user->id}";
        return is_dir($decryptedPath);
    }

    public function updateSession(User $user): void
    {
        $key = "crypto_session:user_{$user->id}";
        $ttl = config('scrybble.cryptfs.session_ttl', self::SESSION_TTL);
        Redis::setex($key, $ttl, now()->timestamp);
    }

    public function getExpiredSessions(): array
    {
        $pattern = "crypto_session:user_*";
        $keys = Redis::keys($pattern);
        $expiredSessions = [];

        $ttl = config('scrybble.cryptfs.session_ttl', self::SESSION_TTL);
        foreach ($keys as $key) {
            $timestamp = Redis::get($key);
            if ($timestamp && (now()->timestamp - $timestamp) > $ttl) {
                // Extract user ID from Redis key
                preg_match('/crypto_session:user_(\d+)/', $key, $matches);
                if (isset($matches[1])) {
                    $expiredSessions[] = (int) $matches[1];
                }
            }
        }

        return $expiredSessions;
    }

    private function createSession(User $user): void
    {
        $this->updateSession($user);
    }

    private function destroySession(User $user): void
    {
        $key = "crypto_session:user_{$user->id}";
        Redis::del($key);
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
        // Check for gocryptfs.conf file
        return file_exists($encryptedPath . '/gocryptfs.conf');
    }

    /**
     * @throws CryptFSException
     */
    private function initializeEncryptedFolder(string $encryptedPath, string $key): void
    {
        $result = Process::timeout(30)->run([
            'gocryptfs', '-init', $encryptedPath, '-extpass', 'echo', '-extpass', $key
        ]);

        if (!$result->successful()) {
            throw new CryptFSException("Failed to initialize encrypted folder: " . $result->errorOutput());
        }

        Log::info("Initialized new encrypted folder at {$encryptedPath}");
    }

    private function migrateLegacyStorage(User $user): bool
    {
        $userId = $user->id;
        $efs = Storage::disk('efs');
        $legacyUserDir = "user-{$userId}";

        if (!$efs->exists($legacyUserDir)) {
            return true;
        }

        // Check if encrypted folder is actually mounted before attempting migration
        if (!$this->isUserFolderMounted($user)) {
            Log::info("Skipping migration for user {$userId} - encrypted folder not mounted");
            return false;
        }

        Log::info("Starting migration of legacy storage for user {$userId}");

        try {
            $decryptedStorage = UserStorage::get($user);

            FileManipulations::moveFilesRecursively($efs, $legacyUserDir, $decryptedStorage, '');

            if (FileManipulations::verifyFilesMatch($efs, $legacyUserDir, $decryptedStorage, '')) {
                $efs->deleteDirectory($legacyUserDir);
                Log::info("Successfully migrated and cleaned up legacy storage for user {$userId}");
                return true;
            } else {
                Log::error("Migration verification failed for user {$userId}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Migration failed for user {$userId}: " . $e->getMessage());
        }
        return false;
    }
}
