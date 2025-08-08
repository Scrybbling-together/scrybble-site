<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\CryptFSException;
use App\Models\User;
use App\Services\CryptFSService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Creates a Storage for an individual user based on their ID
 * Supports both encrypted (mounted) and unencrypted storage
 */
class UserStorage {
    /**
     * @param User $user
     * @return Filesystem
     * @throws CryptFSException
     */
    public static function get(User $user): Filesystem {
        $cryptFs = app(CryptFSService::class);

        if ($cryptFs->isUserFolderMounted($user)) {
            // User folder is mounted - use decrypted mount point
            $decryptedPath = config('scrybble.cryptfs.decrypted_path') . "/user-{$user->id}";
            return Storage::build([
                'driver' => 'local',
                'root' => $decryptedPath,
                'throw' => false
            ]);
        }

        // Check if encryption is required
        if (config('scrybble.require_encryption', false)) {
            throw new CryptFSException(
                "Cannot access user storage: folder not mounted. Please provide encryption key."
            );
        }

        // Legacy fallback for backwards compatibility during migration
        return self::fallbackLegacyStorage($user);
    }

    /**
     * Delete when storage is moved from /efs/ to encrypted folders for all active users
     */
    private static function fallbackLegacyStorage(User $user): Filesystem
    {
        $efs = Storage::disk('efs');
        $user_dir = "user-{$user->id}/";
        $storage = Storage::build($efs->path($user_dir));

        if (!$storage->exists('')) {
            $storage->makeDirectory('');
        }

        return $storage;
    }
}
