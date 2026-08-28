<?php

namespace App\Http\Controllers;

use App\Models\Sync;
use App\Services\GumroadService;
use App\Services\OnboardingStateService;
use Illuminate\Http\Request;

class SyncUserController extends Controller {
    public function __construct(
        public GumroadService $gumroadService,
        public OnboardingStateService $onboardingStateService
    ) {
    }

    public function __invoke(Request $request) {
        $user = $request->user();

        return [
            'user' => $user,
            'subscription_status' => $this->gumroadService->licenseInfo(),
            'total_syncs' => Sync::forUser($user)->count(),
            'onboarding_state' => $this->onboardingStateService->getState(),
            'rmapi_host' => config('scrybble.rmapi.host')
        ];
    }
}
