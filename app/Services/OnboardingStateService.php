<?php

namespace App\Services;

use App\Enums\DeploymentEnvironment;
use App\Exceptions\MissingRMApiAuthenticationTokenException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OnboardingStateService
{
    public function __construct(public RMapi $rmapi) {}

    public function getState(): string
    {
        /**
         * @var User
         */
        $user = Auth::user();

        if (! $user) {
            return 'unauthenticated';
        }

        if (DeploymentEnvironment::current()->isCommercial() && ! $user->gumroadLicense()->exists()) {
            return 'setup-gumroad';
        }

        try {
            if (! $this->rmapi->isAuthenticated()) {
                return 'setup-one-time-code';
            }
        } catch (MissingRMApiAuthenticationTokenException $e) {
            return 'setup-one-time-code-again';
        }

        return 'ready';
    }
}
