<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\RMapi;
use App\Services\RMapiProcessRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Pins the exact argv that {@see RMapi} hands to the rmapi binary.
 */
final class RMapiArgvTest extends TestCase
{
    use RefreshDatabase;

    private function makeRMapi(RMapiProcessRunner $runner): RMapi
    {
        Storage::fake('efs');
        return new RMapi(User::factory()->create(), $runner);
    }

    public function test_list_passes_path_verbatim(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'ls', '/Daily Journal/'])
            ->willReturn([['[]'], 0]);

        $this->makeRMapi($runner)->list('/Daily Journal/');
    }

    public function test_find_builds_flags_in_order(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'find', '--starred', '--tag=work', '--tag=todo', '/', 'notes'])
            ->willReturn([['[]'], 0]);

        $this->makeRMapi($runner)->find(query: 'notes', starred: true, tags: ['work', 'todo']);
    }

    public function test_getById_passes_id_as_separate_argv_element(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'get', '--id', 'abc-123'])
            ->willReturn([['boom'], 1]);

        // getById is meant to create files, we don't care about file operations so the fake binary fails
        $this->expectException(RuntimeException::class);
        $this->makeRMapi($runner)->getById('abc-123', 'Carol');
    }

    public function test_get_currently_shell_escapes_path(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'get', "/Work/Carol"])
            ->willReturn([['boom'], 1]);

        $this->expectException(RuntimeException::class);
        $this->makeRMapi($runner)->get('/Work/Carol');
    }

    public function test_authenticate_passes_code_via_stdin_not_argv(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([], 'one-time-code')
            ->willReturn([['unrecognised output'], 0]);

        $this->expectException(RuntimeException::class);
        $this->makeRMapi($runner)->authenticate('one-time-code');
    }
}
