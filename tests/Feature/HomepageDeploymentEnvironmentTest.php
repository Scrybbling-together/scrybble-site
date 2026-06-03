<?php

namespace Tests\Feature;

use App\Enums\DeploymentEnvironment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageDeploymentEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_deployment_includes_analytics_script()
    {
        config(['scrybble.deployment_environment' => DeploymentEnvironment::Commercial->value]);

        $this->get('/')->assertSee('analytics.ahrefs.com', false);
    }

    public function test_self_hosted_deployment_omits_analytics_script()
    {
        config(['scrybble.deployment_environment' => DeploymentEnvironment::SelfHosted->value]);

        $this->get('/')->assertDontSee('analytics.ahrefs.com', false);
    }
}
