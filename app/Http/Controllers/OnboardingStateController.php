<?php

namespace App\Http\Controllers;

use App\Services\OnboardingStateService;

class OnboardingStateController extends Controller
{
    public function __invoke(OnboardingStateService $onboarding_state_service)
    {
        return response()->json($onboarding_state_service->getState());
    }
}
