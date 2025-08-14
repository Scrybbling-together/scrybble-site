<?php

namespace App\modules\CryptFS\Services;

use App\Models\User;
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

    public function acquireMountingLock(User $user): bool
    {
        $key = self::LOCK_PREFIX . $user->id;
        $lockTtl = config('scrybble.cryptfs.lock_ttl', self::LOCK_TTL);

        return Redis::set($key, '1', 'EX', $lockTtl, 'NX') === 'OK';
    }

    public function releaseMountingLock(User $user): void
    {
        $key = self::LOCK_PREFIX . $user->id;
        Redis::del($key);
    }

    public function acquireKeyDerivationLock(User $user): bool
    {
        $key = self::KEY_DERIVATION_LOCK_PREFIX . $user->id;
        $lockTtl = config('scrybble.cryptfs.lock_ttl', self::LOCK_TTL);

        return Redis::set($key, '1', 'EX', $lockTtl, 'NX') === 'OK';
    }

    public function releaseKeyDerivationLock(User $user): void
    {
        $key = self::KEY_DERIVATION_LOCK_PREFIX . $user->id;
        Redis::del($key);
    }

    public function acquireKeyConfirmationLock(User $user): bool
    {
        $key = self::KEY_CONFIRMATION_LOCK_PREFIX . $user->id;
        $lockTtl = config('scrybble.cryptfs.lock_ttl', self::LOCK_TTL);

        return Redis::set($key, '1', 'EX', $lockTtl, 'NX') === 'OK';
    }

    public function releaseKeyConfirmationLock(User $user): void
    {
        $key = self::KEY_CONFIRMATION_LOCK_PREFIX . $user->id;
        Redis::del($key);
    }

    public function withMountingLock(User $user, callable $callback)
    {
        if (!$this->acquireMountingLock($user)) {
            throw new RuntimeException("Could not acquire mounting lock for user $user->id");
        }

        try {
            return $callback();
        } finally {
            $this->releaseMountingLock($user);
        }
    }

    public function withKeyDerivationLock(User $user, callable $callback)
    {
        if (!$this->acquireKeyDerivationLock($user)) {
            throw new RuntimeException("Could not acquire key derivation lock for user $user->id");
        }

        try {
            return $callback();
        } finally {
            $this->releaseKeyDerivationLock($user);
        }
    }

    public function withKeyConfirmationLock(User $user, callable $callback)
    {
        if (!$this->acquireKeyConfirmationLock($user)) {
            throw new RuntimeException("Could not acquire key confirmation lock for user $user->id");
        }

        try {
            return $callback();
        } finally {
            $this->releaseKeyConfirmationLock($user);
        }
    }
}
