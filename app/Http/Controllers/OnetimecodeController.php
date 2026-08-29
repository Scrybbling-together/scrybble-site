<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateOnetimecodeRequest;
use App\Services\OnboardingStateService;
use App\Services\RMapi;
use Exception;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class OnetimecodeController extends Controller
{
    public function create(CreateOnetimecodeRequest $request, RMapi $RMapi, OnboardingStateService $onboarding_state_service): JsonResponse
    {
        try {
            $RMapi->authenticate($request->get('code'));
        } catch (InvalidArgumentException|Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['newState' => $onboarding_state_service->getState()]);
    }
}
