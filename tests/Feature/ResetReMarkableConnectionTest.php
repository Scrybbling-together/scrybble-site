<?php

namespace Tests\Feature;

use App\Models\Sync;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResetReMarkableConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_remarkable_connection_clears_auth_and_sync_history()
    {
        Storage::fake('efs');

        $user = User::factory()->create();
        $userDir = "user-{$user->id}";

        // Set up the user's directory with auth files and data
        Storage::disk('efs')->put("$userDir/.rmapi-auth", "devicetoken: \"test-device-token\"\nusertoken: \"test-user-token\"");
        Storage::disk('efs')->put("$userDir/rmapi/tree.cache", 'cached tree data');
        Storage::disk('efs')->put("$userDir/jobs/sync-123/out.zip", 'job output');
        Storage::disk('efs')->put("$userDir/processed/sync-123.zip", 'processed output');
        Storage::disk('efs')->put("$userDir/".hash('sha1', '/test/file.pdf').'.zip', 'downloaded file');

        // Create sync history in the database
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
            'filename' => '/test/file.pdf',
            'sync_id' => 'sync-123',
        ]);

        $syncLog = new SyncLog;
        $syncLog->sync_id = $sync->id;
        $syncLog->message = 'Test log message';
        $syncLog->severity = 'info';
        $syncLog->context = [];
        $syncLog->save();

        // Verify onboarding state is "ready" before reset
        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/sync/onboardingState');
        $response->assertStatus(200);
        $this->assertEquals('ready', $response->json());

        // Perform the reset
        $response = $this->deleteJson('/api/sync/remarkable-connection');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify onboarding state is now "setup-one-time-code"
        $response = $this->getJson('/api/sync/onboardingState');
        $response->assertStatus(200);
        $this->assertEquals('setup-one-time-code', $response->json());

        // Verify user directory is cleared
        Storage::disk('efs')->assertMissing("$userDir/.rmapi-auth");
        Storage::disk('efs')->assertMissing("$userDir/rmapi/tree.cache");
        Storage::disk('efs')->assertMissing("$userDir/jobs/sync-123/out.zip");
        Storage::disk('efs')->assertMissing("$userDir/processed/sync-123.zip");

        // Verify sync history is cleared from database
        $this->assertDatabaseMissing('sync', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('sync_logs', ['sync_id' => $sync->id]);
    }

    public function test_reset_remarkable_connection_only_affects_current_user()
    {
        Storage::fake('efs');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Set up both users' directories
        Storage::disk('efs')->put("user-{$user1->id}/.rmapi-auth", "devicetoken: \"token1\"\nusertoken: \"token1\"");
        Storage::disk('efs')->put("user-{$user2->id}/.rmapi-auth", "devicetoken: \"token2\"\nusertoken: \"token2\"");

        // Create sync history for both users
        Sync::factory()->completed()->create([
            'user_id' => $user1->id,
            'filename' => '/test/file1.pdf',
            'sync_id' => 'sync-user1',
        ]);

        Sync::factory()->completed()->create([
            'user_id' => $user2->id,
            'filename' => '/test/file2.pdf',
            'sync_id' => 'sync-user2',
        ]);

        // Reset user1's connection
        $this->actingAs($user1, 'api');
        $response = $this->deleteJson('/api/sync/remarkable-connection');
        $response->assertStatus(200);

        // User1's data should be cleared
        Storage::disk('efs')->assertMissing("user-{$user1->id}/.rmapi-auth");
        $this->assertDatabaseMissing('sync', ['user_id' => $user1->id]);

        // User2's data should remain intact
        Storage::disk('efs')->assertExists("user-{$user2->id}/.rmapi-auth");
        $this->assertDatabaseHas('sync', ['user_id' => $user2->id]);
    }

    public function test_reset_remarkable_connection_requires_authentication()
    {
        $response = $this->deleteJson('/api/sync/remarkable-connection');
        $response->assertStatus(401);
    }
}
