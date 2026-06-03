<?php

namespace Tests\Unit\Services;

use App\Enums\DeploymentEnvironment;
use App\Models\User;
use App\Services\OnboardingStateService;
use App\Services\RMapi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OnboardingStateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_setup_gumroad_for_unlicensed_user_in_commercial_deployment()
    {
        config(['scrybble.deployment_environment' => DeploymentEnvironment::Commercial->value]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = new OnboardingStateService(Mockery::mock(RMapi::class));

        $this->assertSame('setup-gumroad', $service->getState());
    }
}
