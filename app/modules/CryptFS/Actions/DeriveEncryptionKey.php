<?php

namespace App\modules\CryptFS\Actions;

use App\Models\User;
use App\modules\CryptFS\Services\CryptFSKeyService;
use App\modules\CryptFS\Services\CryptFSSessionService;
use Exception;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DeriveEncryptionKey
{
    public function __construct(
        private readonly CryptFSKeyService $keyService,
        private readonly CryptFSSessionService $sessionService
    ) {}

    function handle(Authenticated|PasswordReset $event): void
    {
        /** @var User $user */
        $user = $event->user;

        if (!$this->keyService->needsKeyDerivation($user)) {
            return;
        }


        $request = request();
        $password = $request->only('password')[0] ?? $request->input('password');

        if (!$password) {
            Log::warning("No password available for key derivation for user $user->id");
            return;
        }

        try {
            $this->sessionService->withKeyDerivationLock($user, function () use ($user, $password) {
                $this->keyService->deriveKey($user, $password);
                Log::info("Key derived and stored for user $user->id");
            });
        } catch (RuntimeException $e) {
            Log::warning("Could not acquire key derivation lock for user $user->id: " . $e->getMessage());
        } catch (Exception $e) {
            Log::error("Key derivation failed for user $user->id: " . $e->getMessage());
        }
    }
}
