<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\SyncUserController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\RmApiHostConfig;
use Tests\TestCase;

#[CoversClass(SyncUserController::class)]
final class SyncUserRmapiHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_user_reports_null_rmapi_host_when_rmfakecloud_host_is_unset(): void
    {
        RmApiHostConfig::set($this->app['config'], null);

        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/sync/user');
        $response->assertStatus(200);
        $response->assertJsonStructure(['rmapi_host']);
        $response->assertJsonPath('rmapi_host', null);
    }

    public function test_sync_user_reports_resolved_rmapi_host_when_rmfakecloud_host_is_set(): void
    {
        RmApiHostConfig::set($this->app['config'], 'https://fake.local/');

        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/sync/user');
        $response->assertStatus(200);
        $response->assertJsonStructure(['rmapi_host']);
        $response->assertJsonPath('rmapi_host', 'https://fake.local');
    }
}
