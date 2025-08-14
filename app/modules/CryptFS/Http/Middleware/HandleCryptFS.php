<?php

namespace App\modules\CryptFS\Http\Middleware;

use App\Exceptions\CryptFSException;
use App\Models\CryptFSTable;
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

        if ($user && !$user->cryptFS) {
            $user->cryptFS()->create([
                'key_needs_derivation' => true
            ]);
        }

        $encryptionKey = $request->header('X-Encryption-Key');

        if (!$encryptionKey && $this->mountingService->isUserFolderMounted($user->id)) {
            $this->sessionService->updateSession($user);
            return $next($request);
        }

        // If encryption key provided, attempt to mount
        if ($encryptionKey) {
            try {
                $this->sessionService->withMountingLock($user, function () use ($user, $encryptionKey) {
                    $this->mountingService->mountUserFolder($user, $encryptionKey);
                    $this->sessionService->updateSession($user);
                });

                $this->keyService->securelyZeroKey($encryptionKey);

                return $next($request);
            } catch (CryptFSException $e) {
                Log::warning("CryptFS: Failed to mount folder for user $user->id: " . $e->getMessage());
                return response()->json([
                    'error' => 'Failed to decrypt user folder. Please check your encryption key.'
                ], 403);
            } catch (SodiumException $e) {
                Log::warning("CryptFS: Sodium failed to memzero." . $e->getMessage());
                return response()->json([
                    'error' => 'Could not clear encryption key from memory. Server is misconfigured or something is deeply wrong. Check ext-sodium and Laravel log'
                ], 500);
            } catch (RuntimeException $e) {
                Log::warning("CryptFS: Failed to acquire lock for user $user->id: " . $e->getMessage());
                return response()->json([
                    'error' => 'System busy, please try again'
                ], 503);
            }
        }

        return $next($request);
    }
}
