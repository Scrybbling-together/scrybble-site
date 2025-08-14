<?php

namespace App\Console\Commands;

use App\Models\User;
use App\modules\CryptFS\Services\CryptFSMountingService;
use App\modules\CryptFS\Services\CryptFSSessionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CleanupExpiredCryptFSSessions extends Command
{
    protected $signature = 'cryptfs:cleanup';

    protected $description = 'Cleanup expired CryptFS sessions and unmount orphaned folders';

    public function __construct(
        private CryptFSSessionService $sessionService,
        private CryptFSMountingService $mountingService
    ) {
        parent::__construct();
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
        $expiredUserIds = $this->sessionService->getExpiredSessions();

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
        $mountedUserIds = $this->mountingService->getAllMountedFolders();
        $orphaned = 0;

        foreach ($mountedUserIds as $userId) {
            // Check if there's a valid session in Redis for this mounted folder
            $user = User::find($userId);
            if (!$user || !$this->sessionService->hasActiveSession($user)) {
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

            if ($this->mountingService->unmountUserFolder($user)) {
                $this->sessionService->destroySession($user);
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

}
