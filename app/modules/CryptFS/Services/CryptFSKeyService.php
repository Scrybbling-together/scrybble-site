<?php

namespace App\modules\CryptFS\Services;

use App\Models\CryptFSTable;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Random\RandomException;
use SodiumException;

class CryptFSKeyService
{
    private const int KEY_DERIVATION_ITERATIONS = 600000;
    private const int SALT_BYTES = 64;
    private const int PENDING_KEY_TTL = 600; // 10 minutes

    /**
     * @throws RandomException
     */
    public function deriveKey(User $user, string $password): string
    {
        $cryptFS = $this->ensureCryptFSRecord($user);
        
        $derivedKey = hash_pbkdf2(
            'sha512', 
            $password, 
            $cryptFS->encryption_key_salt, 
            self::KEY_DERIVATION_ITERATIONS
        );

        $this->storePendingKey($user, $derivedKey);
        
        return $derivedKey;
    }

    public function validateAndDecodeKey(string $encodedKey): string|false
    {
        return base64_decode($encodedKey, true);
    }

    public function getPendingKey(User $user): ?string
    {
        return Cache::get("cryptfs:pending_derived_key:{$user->id}");
    }

    public function storePendingKey(User $user, string $key): void
    {
        Cache::put("cryptfs:pending_derived_key:{$user->id}", $key, self::PENDING_KEY_TTL);
    }

    public function clearPendingKey(User $user): void
    {
        Cache::forget("cryptfs:pending_derived_key:{$user->id}");
    }

    public function needsKeyDerivation(User $user): bool
    {
        $cryptFS = $user->cryptFS;
        
        if (!$cryptFS) {
            return true;
        }

        return $cryptFS->key_needs_derivation ?? true;
    }

    public function markKeyAsStored(User $user): void
    {
        $cryptFS = $user->cryptFS;
        if ($cryptFS) {
            $cryptFS->update(['key_needs_derivation' => false]);
        }
    }

    /**
     * @throws SodiumException
     */
    public function securelyZeroKey(string &$key): void
    {
        sodium_memzero($key);
    }

    /**
     * @throws RandomException
     */
    private function ensureCryptFSRecord(User $user): CryptFSTable
    {
        return $user->cryptFS ?? $user->cryptFS()->create([
            'encryption_key_salt' => bin2hex(random_bytes(self::SALT_BYTES)),
            'key_needs_derivation' => true
        ]);
    }
}