<?php

namespace App\modules\CryptFS\Http\Middleware;

use App\modules\CryptFS\Services\CryptFSKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AppendEncryptionKeyMiddleware
{
    public function __construct(private readonly CryptFSKeyService $keyService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $user = Auth::user();

        $derived_key = $this->keyService->getPendingKey($user);
        if ($derived_key) {
            $response->headers->set('X-Encryption-Key', $derived_key);
        } else {
            Log::warning("Cryptfs: No derived key available for user {$user->id} when requesting /oauth/token");
        }

        return $response;
    }
}
