<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConnectedGumroadLicenseRequest;
use App\Models\User;
use App\Services\GumroadApi;
use App\Services\OnboardingStateService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use JsonException;

class ConnectedGumroadLicenseController extends Controller
{
    public function store(StoreConnectedGumroadLicenseRequest $request, OnboardingStateService $onboarding_state_service, GumroadApi $gumroadApi)
    {
        /** @var User $user */
        $user = Auth::user();

        $license = $request->get('license');

        if ($user->gumroadLicense !== null && $user->gumroadLicense->valid) {
            return response()
                ->json(['error' => 'A license is already connected'], 422);
        }

        try {
            $res = $gumroadApi->verifyLicense($license);
        } catch (GuzzleException) {
            return response()
                ->json(['error' => "License \"$license\" not found, did you fill it in correctly?"], 422);
        }
        try {
            $data = json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return response()
                ->json(['error' => 'Gumroad API error'], 503);
        }

        if ($data['success']) {
            $user->gumroadLicense()->firstOrCreate([
                'valid' => true,
                'license' => $license,
            ]);

            return response()->json(['newState' => $onboarding_state_service->getState()]);
        }

        return response()->json();
    }
}
