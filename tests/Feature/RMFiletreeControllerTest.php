<?php

namespace Tests\Feature;

use App\Models\Sync;
use App\Models\User;
use App\Services\RMapi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RMFiletreeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_filetree_requires_authentication()
    {
        $response = $this->postJson('/api/sync/RMFileTree', []);

        $response->assertStatus(401);
    }

    public function test_filetree_lists_root_directory_by_default()
    {
        $this->actingAs($this->user, 'api');

        $mockRmapi = $this->createMock(RMapi::class);
        $mockRmapi->expects($this->once())
            ->method('list')
            ->with('/')
            ->willReturn(collect([
                [
                    'type' => 'd',
                    'name' => 'Work',
                    'path' => '/Work/',
                    'id' => 'folder-123',
                    'version' => null,
                    'modifiedClient' => null,
                    'currentPage' => null,
                    'tags' => [],
                    'starred' => false,
                ],
                [
                    'type' => 'f',
                    'name' => 'Quick Notes',
                    'path' => '/Quick Notes',
                    'id' => 'doc-456',
                    'version' => 2,
                    'modifiedClient' => '2024-01-15T10:30:00Z',
                    'currentPage' => 0,
                    'tags' => [],
                    'starred' => false,
                ],
            ]));

        $this->app->instance(RMapi::class, $mockRmapi);

        $response = $this->postJson('/api/sync/RMFileTree', []);

        $response->assertStatus(200);
        $response->assertJsonPath('cwd', '/');
        $response->assertJsonCount(2, 'items');
    }

    public function test_filetree_lists_specified_directory()
    {
        $this->actingAs($this->user, 'api');

        $mockRmapi = $this->createMock(RMapi::class);
        $mockRmapi->expects($this->once())
            ->method('list')
            ->with('/Work/')
            ->willReturn(collect([
                [
                    'type' => 'f',
                    'name' => 'Project Plan',
                    'path' => '/Work/Project Plan',
                    'id' => 'doc-789',
                    'version' => 1,
                    'modifiedClient' => '2024-02-01T09:00:00Z',
                    'currentPage' => 3,
                    'tags' => ['Work'],
                    'starred' => true,
                ],
            ]));

        $this->app->instance(RMapi::class, $mockRmapi);

        $response = $this->postJson('/api/sync/RMFileTree', ['path' => '/Work/']);

        $response->assertStatus(200);
        $response->assertJsonPath('cwd', '/Work/');
        // Should have parent directory (..) plus the file
        $response->assertJsonCount(2, 'items');
        $response->assertJsonPath('items.0.name', '..');
        $response->assertJsonPath('items.0.path', '/');
    }

    public function test_filetree_includes_parent_directory_when_not_at_root()
    {
        $this->actingAs($this->user, 'api');

        $mockRmapi = $this->createMock(RMapi::class);
        $mockRmapi->expects($this->once())
            ->method('list')
            ->with('/Work/Projects/')
            ->willReturn(collect([]));

        $this->app->instance(RMapi::class, $mockRmapi);

        $response = $this->postJson('/api/sync/RMFileTree', ['path' => '/Work/Projects/']);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.name', '..');
        $response->assertJsonPath('items.0.path', '/Work/');
    }

    public function test_filetree_does_not_include_parent_at_root()
    {
        $this->actingAs($this->user, 'api');

        $mockRmapi = $this->createMock(RMapi::class);
        $mockRmapi->expects($this->once())
            ->method('list')
            ->with('/')
            ->willReturn(collect([]));

        $this->app->instance(RMapi::class, $mockRmapi);

        $response = $this->postJson('/api/sync/RMFileTree', ['path' => '/']);

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'items');
    }

    public function test_filetree_includes_sync_status_for_files()
    {
        $this->actingAs($this->user, 'api');

        $sync = Sync::factory()->completed()->create([
            'user_id' => $this->user->id,
            'filename' => '/Synced Document',
            'sync_id' => 'sync-123',
        ]);

        $mockRmapi = $this->createMock(RMapi::class);
        $mockRmapi->expects($this->once())
            ->method('list')
            ->willReturn(collect([
                [
                    'type' => 'f',
                    'name' => 'Synced Document',
                    'path' => '/Synced Document',
                    'id' => 'doc-synced',
                    'version' => 1,
                    'modifiedClient' => '2024-01-15T10:30:00Z',
                    'currentPage' => 0,
                    'tags' => [],
                    'starred' => false,
                ],
                [
                    'type' => 'f',
                    'name' => 'Unsynced Document',
                    'path' => '/Unsynced Document',
                    'id' => 'doc-unsynced',
                    'version' => 1,
                    'modifiedClient' => '2024-01-15T10:30:00Z',
                    'currentPage' => 0,
                    'tags' => [],
                    'starred' => false,
                ],
            ]));

        $this->app->instance(RMapi::class, $mockRmapi);

        $response = $this->postJson('/api/sync/RMFileTree', []);

        $response->assertStatus(200);

        $items = $response->json('items');
        $syncedItem = collect($items)->firstWhere('name', 'Synced Document');
        $unsyncedItem = collect($items)->firstWhere('name', 'Unsynced Document');

        $this->assertNotNull($syncedItem['sync']);
        $this->assertEquals($sync->id, $syncedItem['sync']['id']);
        $this->assertNull($unsyncedItem['sync']);
    }

    public function test_filetree_does_not_include_sync_status_for_directories()
    {
        $this->actingAs($this->user, 'api');

        $mockRmapi = $this->createMock(RMapi::class);
        $mockRmapi->expects($this->once())
            ->method('list')
            ->willReturn(collect([
                [
                    'type' => 'd',
                    'name' => 'Work',
                    'path' => '/Work/',
                    'id' => 'folder-123',
                    'version' => null,
                    'modifiedClient' => null,
                    'currentPage' => null,
                    'tags' => [],
                    'starred' => false,
                ],
            ]));

        $this->app->instance(RMapi::class, $mockRmapi);

        $response = $this->postJson('/api/sync/RMFileTree', []);

        $response->assertStatus(200);
        $response->assertJsonPath('items.0.sync', null);
    }

    public function test_filetree_returns_latest_sync_when_multiple_exist()
    {
        $this->actingAs($this->user, 'api');

        // Create older sync
        Sync::factory()->completed()->create([
            'user_id' => $this->user->id,
            'filename' => '/Document',
            'sync_id' => 'old-sync',
            'created_at' => now()->subDays(2),
        ]);

        // Create newer sync
        $latestSync = Sync::factory()->completed()->create([
            'user_id' => $this->user->id,
            'filename' => '/Document',
            'sync_id' => 'new-sync',
            'created_at' => now()->subDay(),
        ]);

        $mockRmapi = $this->createMock(RMapi::class);
        $mockRmapi->expects($this->once())
            ->method('list')
            ->willReturn(collect([
                [
                    'type' => 'f',
                    'name' => 'Document',
                    'path' => '/Document',
                    'id' => 'doc-123',
                    'version' => 1,
                    'modifiedClient' => '2024-01-15T10:30:00Z',
                    'currentPage' => 0,
                    'tags' => [],
                    'starred' => false,
                ],
            ]));

        $this->app->instance(RMapi::class, $mockRmapi);

        $response = $this->postJson('/api/sync/RMFileTree', []);

        $response->assertStatus(200);
        $response->assertJsonPath('items.0.sync.id', $latestSync->id);
    }
}
