<?php

namespace App\modules\CryptFS\Http\Middleware;

use App\Exceptions\CryptFSException;
use App\modules\CryptFS\Services\CryptFSKeyService;
use App\modules\CryptFS\Services\CryptFSMountingService;
use App\modules\CryptFS\Services\CryptFSSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use SodiumException;
use Symfony\Component\HttpFoundation\Response;

class HandleCryptFS
{
    public function __construct(
        private readonly CryptFSKeyService      $keyService,
        private readonly CryptFSMountingService $mountingService,
        private readonly CryptFSSessionService $sessionService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $encryptionKey = $request->header('X-Encryption-Key');

        // If no encryption key provided but folder is already mounted, just update session
        if (!$encryptionKey && $this->mountingService->isUserFolderMounted($user)) {
            $this->sessionService->updateSession($user);
            return $next($request);
        }

        // If encryption key provided, attempt to mount
        if ($encryptionKey) {
            try {
                $decodedKey = $this->keyService->validateAndDecodeKey($encryptionKey);
                if ($decodedKey === false) {
                    return response()->json([
                        'error' => 'Invalid encryption key format'
                    ], 400);
                }

                $this->sessionService->withMountingLock($user, function () use ($user, $decodedKey) {
                    if (!$this->mountingService->isUserFolderMounted($user)) {
                        $this->mountingService->mountUserFolder($user, $decodedKey);
                    }
                    $this->sessionService->updateSession($user);
                });

                $this->keyService->securelyZeroKey($decodedKey);

                return $next($request);
            } catch (CryptFSException $e) {
                Log::warning("Failed to mount folder for user {$user->id}: " . $e->getMessage());
                return response()->json([
                    'error' => 'Failed to decrypt user folder. Please check your encryption key.'
                ], 403);
            } catch (SodiumException $e) {
                Log::warning("Sodium failed to memzero." . $e->getMessage());
                return response()->json([
                    'error' => 'Could not clear encryption key from memory. Server is misconfigured or something is deeply wrong. Check ext-sodium and Laravel log'
                ], 500);
            } catch (RuntimeException $e) {
                Log::warning("Failed to acquire lock for user {$user->id}: " . $e->getMessage());
                return response()->json([
                    'error' => 'System busy, please try again'
                ], 503);
            }
        }

        $response = $next($request);

        if (Str::endsWith($request->route(), "/oauth/token")) {
            $derived_key = $this->keyService->getPendingKey($user);
            if ($derived_key) {
                $response->headers->set('X-Encryption-Key', $derived_key);
            } else {
                Log::warning("Cryptfs: No derived key available for user {$user->id} when requesting /oauth/token");
            }
        }

        return $response;
    }
}
