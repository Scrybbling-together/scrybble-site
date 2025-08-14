<?php

namespace App\modules\CryptFS\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;
use Random\RandomException;

class DeriveEncryptionKey
{
    /**
     * @throws RandomException
     */
    function handle(Authenticated|PasswordReset $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $cryptFS = $user->cryptFS ?? $user->cryptFS()->create([
            'encryption_key_salt' => bin2hex(random_bytes(64))
        ]);
        $request = request();

        $password = $request->only('password')[0] ?? $request->input('password');

        Log::info("Logged in or password reset, password is: '{$password}'");
        Log::info(json_encode(request()->all()));

//        $derivedKey = $this->deriveKey($password, $cryptFS->encryption_key_salt);
//        Cache::put("encryption_key_{$user->id}", $derivedKey, 60 * 60);
    }

    private function deriveKey(string $password, string $salt): string
    {
        return hash_pbkdf2("sha512", $password, $salt, iterations: 600000);
    }
}
