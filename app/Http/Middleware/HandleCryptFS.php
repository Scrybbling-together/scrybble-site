<?php

namespace App\Http\Middleware;

use App\Services\CryptFSService;
use App\Exceptions\CryptFSException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        // Skip if user is not authenticated
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
                // Decode the base64-encoded key
                $decodedKey = base64_decode($encryptionKey, true);
                if ($decodedKey === false) {
                    return response()->json([
                        'error' => 'Invalid encryption key format'
                    ], 400);
                }

                // Mount the user folder
                $this->cryptFSService->mountUserFolder($user, $decodedKey);
                
                // Clear the key from memory
                sodium_memzero($decodedKey);
                
                return $next($request);
            } catch (CryptFSException $e) {
                Log::warning("Failed to mount folder for user {$user->id}: " . $e->getMessage());
                return response()->json([
                    'error' => 'Failed to decrypt user folder. Please check your encryption key.'
                ], 403);
            }
        }

        // Check if encryption is required
        if (config('scrybble.require_encryption', false)) {
            return response()->json([
                'error' => 'Encryption key required. Please provide X-Encryption-Key header.'
            ], 401);
        }

        // Allow request to proceed without encryption (legacy mode)
        return $next($request);
    }
}