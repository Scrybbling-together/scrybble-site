<?php

namespace App\modules\CryptFS\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

class CryptFSSessionService
{
    private const int SESSION_TTL = 600; // 10 minutes
    private const int LOCK_TTL = 60; // 1 minute
    private const string SESSION_PREFIX = 'crypto_session:user_';
    private const string LOCK_PREFIX = 'cryptfs:mounting:';
    private const string KEY_DERIVATION_LOCK_PREFIX = 'cryptfs:key-derivation:';
    private const string KEY_CONFIRMATION_LOCK_PREFIX = 'cryptfs:key_confirmation:';

    public function createSession(User $user): void
    {
        $this->updateSession($user);
    }

    public function updateSession(User $user): void
    {
        $key = self::SESSION_PREFIX . $user->id;
        $ttl = config('scrybble.cryptfs.session_ttl', self::SESSION_TTL);
        Redis::setex($key, $ttl, now()->timestamp);
    }

    public function hasActiveSession(User $user): bool
    {
        $key = self::SESSION_PREFIX . $user->id;
        return Redis::exists($key);
    }

    public function destroySession(User $user): void
    {
        $key = self::SESSION_PREFIX . $user->id;
        Redis::del($key);
    }

    public function getExpiredSessions(): array
    {
        $pattern = self::SESSION_PREFIX . '*';
        $keys = Redis::keys($pattern);
        $expiredSessions = [];

        $ttl = config('scrybble.cryptfs.session_ttl', self::SESSION_TTL);
        foreach ($keys as $key) {
            $timestamp = Redis::get($key);
            if ($timestamp && (now()->timestamp - $timestamp) > $ttl) {
                preg_match('/crypto_session:user_(\d+)/', $key, $matches);
                if (isset($matches[1])) {
                    $expiredSessions[] = (int) $matches[1];
                }
            }
        }

        return $expiredSessions;
    }

    public function withMountingLock(User $user, callable $callback)
    {
        return Cache::Lock(self::LOCK_PREFIX . $user->id, 10)->block(10, $callback);
    }

    public function withKeyDerivationLock(User $user, callable $callback)
    {
        return Cache::Lock(self::KEY_DERIVATION_LOCK_PREFIX . $user->id, 10)->block(10, $callback);
    }

    public function withKeyConfirmationLock(User $user, callable $callback)
    {
        return Cache::Lock(self::KEY_CONFIRMATION_LOCK_PREFIX . $user->id, 10)->block(10, $callback);
    }
}
