<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CryptFSService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CleanupExpiredCryptFSSessions extends Command
{
    protected $signature = 'cryptfs:cleanup';
    
    protected $description = 'Cleanup expired CryptFS sessions and unmount orphaned folders';

    private CryptFSService $cryptFSService;

    public function __construct(CryptFSService $cryptFSService)
    {
        parent::__construct();
        $this->cryptFSService = $cryptFSService;
    }

    public function handle(): int
    {
        $this->info('Starting CryptFS cleanup...');
        
        // Step 1: Clean up expired sessions from Redis
        $expiredFromRedis = $this->cleanupExpiredSessions();
        
        // Step 2: Find and clean up orphaned mount points
        $orphanedMounts = $this->cleanupOrphanedMounts();
        
        $total = $expiredFromRedis + $orphanedMounts;
        $this->info("Cleanup complete: {$total} folders unmounted ({$expiredFromRedis} expired, {$orphanedMounts} orphaned)");
        
        return 0;
    }

    private function cleanupExpiredSessions(): int
    {
        $expiredUserIds = $this->cryptFSService->getExpiredSessions();
        
        if (empty($expiredUserIds)) {
            $this->info('No expired sessions found in Redis');
            return 0;
        }

        $unmounted = 0;
        
        foreach ($expiredUserIds as $userId) {
            if ($this->unmountUserFolder($userId, 'expired session')) {
                $unmounted++;
            }
        }
        
        return $unmounted;
    }

    private function cleanupOrphanedMounts(): int
    {
        $decryptedBasePath = config('scrybble.cryptfs.decrypted_path');
        
        if (!is_dir($decryptedBasePath)) {
            return 0;
        }

        $mountedFolders = glob($decryptedBasePath . '/user-*', GLOB_ONLYDIR);
        $orphaned = 0;

        foreach ($mountedFolders as $folderPath) {
            // Extract user ID from folder name
            $folderName = basename($folderPath);
            if (!preg_match('/^user-(\d+)$/', $folderName, $matches)) {
                continue;
            }
            
            $userId = (int) $matches[1];
            
            // Check if this mount point is actually mounted
            if (!$this->isFolderMounted($folderPath)) {
                // Not mounted, just remove the empty directory
                if (is_dir($folderPath) && $this->isDirectoryEmpty($folderPath)) {
                    rmdir($folderPath);
                }
                continue;
            }
            
            // Check if there's a valid session in Redis
            $sessionKey = "crypto_session:user_{$userId}";
            $sessionExists = Redis::exists($sessionKey);
            
            if (!$sessionExists) {
                // Orphaned mount - no session in Redis
                $this->warn("Found orphaned mount for user {$userId} (no Redis session)");
                Log::warning("CryptFS cleanup: Orphaned mount found for user {$userId} - likely due to Redis restart");
                
                if ($this->unmountUserFolder($userId, 'orphaned mount')) {
                    $orphaned++;
                }
            }
        }
        
        return $orphaned;
    }

    private function unmountUserFolder(int $userId, string $reason): bool
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                $this->warn("User {$userId} not found, attempting direct unmount");
                return $this->directUnmount($userId);
            }

            if ($this->cryptFSService->unmountUserFolder($user)) {
                $this->info("Unmounted folder for user {$userId} ({$reason})");
                return true;
            } else {
                $this->error("Failed to unmount folder for user {$userId} ({$reason})");
                return false;
            }
        } catch (\Exception $e) {
            $this->error("Error unmounting user {$userId} ({$reason}): " . $e->getMessage());
            Log::error("CryptFS cleanup error for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    private function directUnmount(int $userId): bool
    {
        $decryptedPath = config('scrybble.cryptfs.decrypted_path') . "/user-{$userId}";
        
        if (!$this->isFolderMounted($decryptedPath)) {
            return true; // Already unmounted
        }

        $result = \Illuminate\Support\Facades\Process::timeout(10)->run([
            'fusermount', '-u', $decryptedPath
        ]);

        if ($result->successful()) {
            // Clean up Redis session if it exists
            $sessionKey = "crypto_session:user_{$userId}";
            Redis::del($sessionKey);
            
            $this->info("Successfully performed direct unmount for user {$userId}");
            return true;
        }

        $this->error("Direct unmount failed for user {$userId}: " . $result->errorOutput());
        return false;
    }

    private function isFolderMounted(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $result = \Illuminate\Support\Facades\Process::run(['mountpoint', '-q', $path]);
        return $result->successful();
    }

    private function isDirectoryEmpty(string $path): bool
    {
        $handle = opendir($path);
        while (false !== ($entry = readdir($handle))) {
            if ($entry !== '.' && $entry !== '..') {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }
}