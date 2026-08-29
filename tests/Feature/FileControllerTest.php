<?php

namespace Tests\Feature;

use App\Jobs\DownloadRemarkableFileJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_request_sync_requires_authentication()
    {
        $response = $this->postJson('/api/sync/file', [
            'file' => '/Test Document',
        ]);

        $response->assertStatus(401);
    }

    public function test_request_sync_with_legacy_file_path()
    {
        Queue::fake();

        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/sync/file', [
            'file' => '/Books/My Document',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'sync_id',
            'filename',
        ]);
        $response->assertJsonPath('filename', '/Books/My Document');

        Queue::assertPushed(DownloadRemarkableFileJob::class, function ($job) {
            // Legacy path-based request should set input_filename and rm_file_id should be null
            $context = $job->sync_context;

            return $context->input_filename === '/Books/My Document'
                && $context->rm_file_id === null;
        });
    }

    public function test_request_sync_with_rm_file_id()
    {
        Queue::fake();

        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/sync/file', [
            'rmFileId' => 'abc123-def456-789',
            'name' => 'My Document',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'sync_id',
            'filename',
        ]);
        $response->assertJsonPath('filename', 'My Document');

        Queue::assertPushed(DownloadRemarkableFileJob::class, function ($job) {
            // ID-based request should set both rm_file_id and input_filename (for display)
            $context = $job->sync_context;

            return $context->rm_file_id === 'abc123-def456-789'
                && $context->input_filename === 'My Document';
        });
    }

    public function test_request_sync_requires_file_or_rm_file_id()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/sync/file', []);

        $response->assertStatus(422);
    }

    public function test_request_sync_rm_file_id_requires_name()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/sync/file', [
            'rmFileId' => 'abc123-def456-789',
        ]);

        $response->assertStatus(422);
    }

    public function test_request_sync_prefers_rm_file_id_over_file_when_both_provided()
    {
        Queue::fake();

        $this->actingAs($this->user, 'api');

        // When both are provided, rmFileId + name should be used
        $response = $this->postJson('/api/sync/file', [
            'file' => '/Some/Path',
            'rmFileId' => 'abc123-def456-789',
            'name' => 'My Document',
        ]);

        $response->assertStatus(200);

        Queue::assertPushed(DownloadRemarkableFileJob::class, function ($job) {
            $context = $job->sync_context;

            return $context->rm_file_id === 'abc123-def456-789'
                && $context->input_filename === 'My Document';
        });
    }

    public function test_request_sync_validates_file_is_string()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/sync/file', [
            'file' => 123,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_request_sync_validates_rm_file_id_is_string()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/sync/file', [
            'rmFileId' => 123,
            'name' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rmFileId']);
    }

    public function test_request_sync_validates_name_is_string()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/sync/file', [
            'rmFileId' => 'abc123',
            'name' => 123,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
