<?php

namespace App\modules\CryptFS\Http\Middleware;

use App\Exceptions\CryptFSException;
use App\modules\CryptFS\Services\CryptFSService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SodiumException;
use Symfony\Component\HttpFoundation\Response;

class HandleCryptFS
{
    private CryptFSService $cryptFSService;

    public function __construct(CryptFSService $cryptFSService)
    {
        $this->cryptFSService = $cryptFSService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $encryptionKey = $request->header('X-Encryption-Key');

        // If no encryption key provided but folder is already mounted, just update session
        if (!$encryptionKey && $this->cryptFSService->isUserFolderMounted($user)) {
            $this->cryptFSService->updateSession($user);
            return $next($request);
        }

        // If encryption key provided, attempt to mount
        if ($encryptionKey) {
            try {
                $decodedKey = base64_decode($encryptionKey, true);
                if ($decodedKey === false) {
                    return response()->json([
                        'error' => 'Invalid encryption key format'
                    ], 400);
                }

                $this->cryptFSService->mountUserFolder($user, $decodedKey);

                sodium_memzero($decodedKey);

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
            }
        }

        $response = $next($request);

        if (Str::endsWith($request->route(), "/oauth/token")) {
            $derived_key = Cache::get("cryptfs:pending_derived_key:{$user->id}");
            if ($derived_key) {
                $response->headers->set('X-Encryption-Key', $derived_key);
            } else {
                Log::warning("Cryptfs: No derived key available for user {$user->id} when requesting /oauth/token");
            }
        }

        return $response;
    }
}
