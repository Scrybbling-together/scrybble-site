<?php

namespace App\modules\CryptFS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\modules\CryptFS\Services\CryptFSKeyService;
use App\modules\CryptFS\Services\CryptFSSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfirmKeyReceivedController extends Controller
{
    public function __construct(private readonly CryptFSKeyService $keyService, private readonly CryptFSSessionService $sessionService)
    {
    }

    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if ($request->hasHeader("X-Encryption-Key")) {
            if ($this->keyService->getPendingKey($user) === $request->header("X-Encryption-Key")) {
                $this->sessionService->withKeyConfirmationLock($user, function () use ($user) {
                    $this->keyService->markKeyAsStored($user);
                    $this->keyService->clearPendingKey($user);
                });
            } else {
                return response()->json([
                    'error' => "Invalid encryption key"
                ], 422);
            }
        }

        return response()->json();
    }
}
