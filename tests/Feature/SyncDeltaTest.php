<?php

namespace Tests\Feature;

use App\Models\Sync;
use App\Models\User;
use App\Services\DownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Tests\TestCase;

class SyncDeltaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_delta_excludes_files_missing_from_filesystem()
    {
        $user = User::factory()->create();

        $existingSync = Sync::factory()->completed()->create([
            'user_id' => $user->id,
            'filename' => '/test/existing_file.pdf',
            'sync_id' => 'existing-sync-123'
        ]);

         Sync::factory()->completed()->create([
            'user_id' => $user->id,
            'filename' => '/test/missing_file.pdf',
            'sync_id' => 'missing-sync-456'
        ]);

        $downloadService = $this->createMock(DownloadService::class);
        $downloadService->expects($this->exactly(2))
            ->method('prepareProcessedRemarksZipUrl')
            ->willReturnCallback(function ($user_id, $sync_id) {
                if ($sync_id === 'existing-sync-123') {
                    return 'https://example.com/existing-file-url';
                } else {
                    throw new GoneHttpException("File with sync id {$sync_id} has been deleted");
                }
            });

        $this->app->instance(DownloadService::class, $downloadService);

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/sync/delta');
        $response->assertStatus(200);
        $responseData = $response->json();

        $this->assertCount(1, $responseData);
        $this->assertEquals($existingSync->id, $responseData[0]['id']);
        $this->assertEquals('/test/existing_file.pdf', $responseData[0]['filename']);
        $this->assertArrayHasKey('download_url', $responseData[0]);

        $filenames = collect($responseData)->pluck('filename')->toArray();
        $this->assertNotContains('/test/missing_file.pdf', $filenames);
    }
}
