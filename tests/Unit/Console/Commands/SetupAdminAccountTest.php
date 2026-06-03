<?php

namespace Tests\Unit\Console\Commands;

use App\Enums\DeploymentEnvironment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupAdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_refuses_to_run_in_commercial_deployment()
    {
        config(['scrybble.deployment_environment' => DeploymentEnvironment::Commercial->value]);

        $this->artisan('app:setup-admin-account')
            ->expectsOutput('This command can only be run in a self-hosted environment.')
            ->assertExitCode(1);
    }

    public function test_can_create_admin_account_in_self_hosted_deployment()
    {
        config(['scrybble.deployment_environment' => DeploymentEnvironment::SelfHosted->value]);

        $this->artisan('app:setup-admin-account')
            ->expectsQuestion('Enter admin username', 'admin')
            ->expectsQuestion('Enter admin password', 'secret-pw')
            ->expectsQuestion('Confirm admin password', 'secret-pw')
            ->expectsQuestion('Enter admin e-mail', 'admin@example.com')
            ->expectsOutput('Admin account created successfully!')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'id' => 1,
            'name' => 'admin',
            'email' => 'admin@example.com',
        ]);
    }
}
