<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Events\ReMarkableAuthenticatedEvent;
use App\Helpers\UserStorage;
use App\Models\User;
use App\Services\RMapi;
use App\Services\RMapiProcessOutput;
use App\Services\RMapiProcessRunner;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end behavior of {@see RMapi} command methods:
 * happy paths, side effects (events, Redis, storage),
 * and the FileNotFoundException branch of get/getById.
 */
final class RMapiBehaviorTest extends TestCase
{
    use RefreshDatabase;

    private function processOutput(string $stdout = '', string $stderr = '', int $exitCode = 0): RMapiProcessOutput
    {
        return new RMapiProcessOutput(
            combined: $stdout . $stderr,
            stdout:   $stdout,
            stderr:   $stderr,
            exitCode: $exitCode,
        );
    }

    public function test_authenticate_returns_true_and_dispatches_event_on_success(): void
    {
        Event::fake();
        Storage::fake('efs');
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')->willReturn($this->processOutput(stdout: 'Refreshing tree...'));

        $rmapi = new RMapi(User::factory()->create(), $runner);

        $this->assertTrue($rmapi->authenticate('one-time-code'));
        Event::assertDispatched(ReMarkableAuthenticatedEvent::class);
    }

    public function test_refresh_soft_returns_true_and_sets_redis_ttl(): void
    {
        Storage::fake('efs');
        $user = User::factory()->create();
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')->willReturn($this->processOutput(exitCode: 0));

        $key = "rmapi:lastRefreshed:{$user->id}";
        Redis::del($key);

        $rmapi = new RMapi($user, $runner);

        $this->assertTrue($rmapi->refresh('soft'));
        $this->assertGreaterThan(0, Redis::ttl($key));
    }

    public function test_refresh_hard_deletes_tree_cache_and_sets_ttl(): void
    {
        Storage::fake('efs');
        $user = User::factory()->create();
        $storage = UserStorage::get($user);
        $storage->put('rmapi/tree.cache', 'cached-data');

        $runner = $this->createMock(RMapiProcessRunner::class);
        $key = "rmapi:lastRefreshed:{$user->id}";
        Redis::del($key);

        $rmapi = new RMapi($user, $runner);

        $this->assertTrue($rmapi->refresh('hard'));
        $this->assertFalse($storage->exists('rmapi/tree.cache'));
        $this->assertGreaterThan(0, Redis::ttl($key));
    }

    public function test_get_moves_downloaded_file_to_hashed_location_and_returns_metadata(): void
    {
        Storage::fake('efs');
        $user = User::factory()->create();
        $storage = UserStorage::get($user);
        $storage->put('Carol.rmdoc', 'fake-zip-content');

        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')->willReturn($this->processOutput(stdout: 'downloaded'));

        $rmapi = new RMapi($user, $runner);
        $result = $rmapi->get('/Work/Carol');

        $hashed = RMapi::hashedFilepath('/Work/Carol');
        $this->assertTrue($storage->exists($hashed));
        $this->assertFalse($storage->exists('Carol.rmdoc'));
        $this->assertSame($hashed, $result['downloaded_zip_location']);
        $this->assertSame('/Work', $result['folder']);
        $this->assertSame('downloaded', $result['output']);
    }

    public function test_get_throws_FileNotFoundException_when_rmapi_says_file_doesnt_exist(): void
    {
        Storage::fake('efs');
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')->willReturn(
            $this->processOutput(stderr: "rmapi: file doesn't exist anywhere", exitCode: 1)
        );

        $this->expectException(FileNotFoundException::class);
        (new RMapi(User::factory()->create(), $runner))->get('/Work/Missing');
    }

    public function test_getById_moves_downloaded_file_to_hashed_location(): void
    {
        Storage::fake('efs');
        $user = User::factory()->create();
        $storage = UserStorage::get($user);
        $storage->put('Carol.rmdoc', 'fake-zip-content');

        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')->willReturn($this->processOutput(stdout: 'downloaded'));

        $rmapi = new RMapi($user, $runner);
        $result = $rmapi->getById('abc-123', 'Carol');

        $hashed = RMapi::hashedFilepath('abc-123');
        $this->assertTrue($storage->exists($hashed));
        $this->assertFalse($storage->exists('Carol.rmdoc'));
        $this->assertSame($hashed, $result['downloaded_zip_location']);
        $this->assertSame('/', $result['folder']);
        $this->assertSame('downloaded', $result['output']);
    }

    public function test_getById_throws_FileNotFoundException_when_rmapi_says_doesnt_exist(): void
    {
        Storage::fake('efs');
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')->willReturn(
            $this->processOutput(stderr: "this file doesn't exist", exitCode: 1)
        );

        $this->expectException(FileNotFoundException::class);
        (new RMapi(User::factory()->create(), $runner))->getById('abc-123', 'Carol');
    }
}