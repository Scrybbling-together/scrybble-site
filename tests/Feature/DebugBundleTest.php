<?php

namespace Tests\Feature;

use App\Models\RemarkableDocumentShare;
use App\Models\Sync;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\DownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Tests\TestCase;
use ZipArchive;

class DebugBundleTest extends TestCase
{
    use RefreshDatabase;

    private function mockDownloadService(): void
    {
        $downloadService = $this->createMock(DownloadService::class);
        $downloadService->method('prepareRMNZipUrl')
            ->willReturn('https://example.com/mocked-url.rmn');
        $this->app->instance(DownloadService::class, $downloadService);
    }

    public function test_admin_can_access_any_syncs_input_endpoint(): void
    {
        $this->mockDownloadService();

        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($admin, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertRedirect();
    }

    public function test_user_can_access_their_own_syncs_input_endpoint(): void
    {
        $this->mockDownloadService();

        $user = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertRedirect();
    }

    public function test_user_can_access_publicly_shared_sync(): void
    {
        $this->mockDownloadService();

        $owner = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $owner->id,
        ]);
        RemarkableDocumentShare::factory()->openAccess()->create([
            'user_id' => $owner->id,
            'sync_id' => $sync->id,
        ]);

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertRedirect();
    }

    public function test_guest_can_access_publicly_shared_sync(): void
    {
        $this->mockDownloadService();

        $owner = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $owner->id,
        ]);
        RemarkableDocumentShare::factory()->openAccess()->create([
            'user_id' => $owner->id,
            'sync_id' => $sync->id,
        ]);

        // No actingAs - guest request
        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertRedirect();
    }

    public function test_user_cannot_access_another_users_private_sync(): void
    {
        $owner = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $owner->id,
        ]);

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'api');

        $response = $this->getJson("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_private_sync(): void
    {
        $owner = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $owner->id,
        ]);

        // No actingAs - guest request
        $response = $this->getJson("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertStatus(403);
    }

    public function test_input_endpoint_returns_redirect_to_signed_url(): void
    {
        $user = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $downloadService = $this->createMock(DownloadService::class);
        $downloadService->expects($this->once())
            ->method('prepareRMNZipUrl')
            ->with($user->id, $sync->sync_id)
            ->willReturn('https://example.com/signed-url-for-input.rmn');

        $this->app->instance(DownloadService::class, $downloadService);

        $this->actingAs($user, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertRedirect('https://example.com/signed-url-for-input.rmn');
    }

    public function test_input_endpoint_returns_410_when_files_are_missing(): void
    {
        $user = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $downloadService = $this->createMock(DownloadService::class);
        $downloadService->expects($this->once())
            ->method('prepareRMNZipUrl')
            ->willThrowException(new GoneHttpException('Input files do not exist anymore'));

        $this->app->instance(DownloadService::class, $downloadService);

        $this->actingAs($user, 'api');

        $response = $this->getJson("/api/debug-bundle/{$sync->sync_id}/input");

        $response->assertStatus(410);
    }

    public function test_full_bundle_contains_sync_json(): void
    {
        $user = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
            'filename' => '/test/document.pdf',
        ]);

        $this->actingAs($user, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/full");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');

        // For BinaryFileResponse, get the file path directly
        $filePath = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($filePath) === true, 'Failed to open zip file');

        // Verify sync.json exists
        $syncJson = $zip->getFromName('sync.json');
        $this->assertNotFalse($syncJson, 'sync.json not found in bundle');

        $syncData = json_decode($syncJson, true);
        $this->assertEquals($sync->id, $syncData['id']);
        $this->assertEquals($sync->sync_id, $syncData['sync_id']);
        $this->assertEquals('/test/document.pdf', $syncData['filename']);

        $zip->close();
    }

    public function test_full_bundle_contains_logs_json(): void
    {
        $user = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        // Create some log entries
        SyncLog::factory()->create([
            'sync_id' => $sync->id,
            'message' => 'Started processing',
            'severity' => 'info',
        ]);
        SyncLog::factory()->create([
            'sync_id' => $sync->id,
            'message' => 'Processing complete',
            'severity' => 'info',
        ]);

        $this->actingAs($user, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/full");

        $response->assertStatus(200);

        $filePath = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive;
        $zip->open($filePath);

        // Verify logs.json exists
        $logsJson = $zip->getFromName('logs.json');
        $this->assertNotFalse($logsJson, 'logs.json not found in bundle');

        $logsData = json_decode($logsJson, true);
        $this->assertCount(2, $logsData);
        $this->assertEquals('Started processing', $logsData[0]['message']);
        $this->assertEquals('Processing complete', $logsData[1]['message']);

        $zip->close();
    }

    public function test_full_bundle_includes_available_files(): void
    {
        $storage = Storage::disk('efs');

        $user = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $userId = $user->id;
        $syncId = $sync->sync_id;

        // Create test files in storage
        $storage->put("user-{$userId}/jobs/{$syncId}/extractedFiles/page1.rm", 'test rm content');
        $storage->put("user-{$userId}/jobs/{$syncId}/extractedFiles/page2.rm", 'test rm content 2');
        $storage->put("user-{$userId}/jobs/{$syncId}/out/output.md", '# Test output');
        $storage->put("user-{$userId}/processed/{$syncId}.zip", 'fake zip content');
        $storage->put("user-{$userId}/input_documents/{$syncId}.rmn", 'fake rmn content');

        $this->actingAs($user, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/full");

        $response->assertStatus(200);

        $filePath = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive;
        $zip->open($filePath);

        // Verify extractedFiles are included
        $this->assertNotFalse($zip->getFromName('extractedFiles/page1.rm'), 'extractedFiles/page1.rm not found');
        $this->assertNotFalse($zip->getFromName('extractedFiles/page2.rm'), 'extractedFiles/page2.rm not found');

        // Verify out directory is included
        $this->assertNotFalse($zip->getFromName('out/output.md'), 'out/output.md not found');

        // Verify processed.zip is included
        $this->assertNotFalse($zip->getFromName('processed.zip'), 'processed.zip not found');

        // Verify input.rmn is included
        $this->assertNotFalse($zip->getFromName('input.rmn'), 'input.rmn not found');

        $zip->close();

        // Cleanup test files
        $storage->deleteDirectory("user-{$userId}");
    }

    public function test_full_bundle_handles_partial_files_gracefully(): void
    {
        $storage = Storage::disk('efs');

        $user = User::factory()->create();
        $sync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $userId = $user->id;
        $syncId = $sync->sync_id;

        // Only create some files (not all)
        $storage->put("user-{$userId}/jobs/{$syncId}/extractedFiles/page1.rm", 'test rm content');
        // No out directory
        // No processed.zip
        // No input.rmn

        $this->actingAs($user, 'api');

        $response = $this->get("/api/debug-bundle/{$sync->sync_id}/full");

        // Should still return 200
        $response->assertStatus(200);

        $filePath = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive;
        $zip->open($filePath);

        // sync.json and logs.json should always be present
        $this->assertNotFalse($zip->getFromName('sync.json'), 'sync.json not found');
        $this->assertNotFalse($zip->getFromName('logs.json'), 'logs.json not found');

        // extractedFiles should be present (we created it)
        $this->assertNotFalse($zip->getFromName('extractedFiles/page1.rm'), 'extractedFiles/page1.rm not found');

        // These should NOT be present (we didn't create them)
        $this->assertFalse($zip->getFromName('out/output.md'), 'out/output.md should not exist');
        $this->assertFalse($zip->getFromName('processed.zip'), 'processed.zip should not exist');
        $this->assertFalse($zip->getFromName('input.rmn'), 'input.rmn should not exist');

        $zip->close();

        // Cleanup test files
        $storage->deleteDirectory("user-{$userId}");
    }
}
