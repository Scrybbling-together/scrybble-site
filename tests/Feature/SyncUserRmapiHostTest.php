<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\RmApiHostConfig;
use Tests\TestCase;

/**
 * Covers the rmapi_host field on GET /api/sync/user, which tells the
 * frontend where to send the user to retrieve their one-time code:
 * null (official reMarkable cloud) or the resolved RMFAKECLOUD_HOST.
 */
#[CoversClass()]
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
