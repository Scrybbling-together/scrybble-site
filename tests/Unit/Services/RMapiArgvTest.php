<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\RMApi\RMApiFindFailedException;
use App\Exceptions\RMApi\RMApiGetFailedException;
use App\Exceptions\RMApi\RMApiInvalidCodeException;
use App\Exceptions\RMApi\RMApiJsonParseException;
use App\Exceptions\RMApi\RMApiListFailedException;
use App\Exceptions\RMApi\RMApiRefreshFailedException;
use App\Exceptions\RMApi\RMApiTokenCreationFailedException;
use App\Exceptions\RMApi\RMApiUnknownAuthOutputException;
use App\Exceptions\RMApi\RMApiZipMoveFailedException;
use App\Models\User;
use App\Services\RMapi;
use App\Services\RMapiProcessOutput;
use App\Services\RMapiProcessRunner;
use Eloquent\Pathogen\Exception\NonAbsolutePathException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Pins the exact argv handed to the rmapi binary.
 */
#[CoversClass(RMapi::class)]
final class RMapiArgvTest extends TestCase
{
    use RefreshDatabase;

    private function makeRMapi(RMapiProcessRunner $runner): RMapi
    {
        Storage::fake('efs');

        return new RMapi(User::factory()->create(), $runner);
    }

    private function processOutput(string $stdout = '', string $stderr = '', int $exitCode = 0): RMapiProcessOutput
    {
        return new RMapiProcessOutput(
            combined: $stdout.$stderr,
            stdout: $stdout,
            stderr: $stderr,
            exitCode: $exitCode,
        );
    }

    public function test_list_passes_path_verbatim(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'ls', '/Daily Journal/'])
            ->willReturn($this->processOutput(stdout: '[]'));

        $this->makeRMapi($runner)->list('/Daily Journal/');
    }

    public function test_list_validates_path_is_absolute(): void
    {
        // get() guards against flag-like paths via AbsolutePath::fromString.
        // list() should do the same.
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->never())->method('run');

        $this->expectException(NonAbsolutePathException::class);
        $this->makeRMapi($runner)->list('--help');
    }

    public function test_list_parses_json_from_stdout_only(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(
                stdout: '[{"id":"abc","name":"Notes","type":"DocumentType"}]',
                stderr: 'Refreshing tree...',
            ));

        $items = $this->makeRMapi($runner)->list('/');

        $this->assertCount(1, $items);
        $this->assertSame('Notes', $items->first()['name']);
    }

    public function test_list_throws_rm_api_json_parse_exception_on_invalid_json(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stdout: 'not valid json at all'));

        $this->expectException(RMApiJsonParseException::class);
        $this->makeRMapi($runner)->list('/');
    }

    public function test_find_throws_rm_api_json_parse_exception_on_invalid_json(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stdout: 'not valid json at all'));

        $this->expectException(RMApiJsonParseException::class);
        $this->makeRMapi($runner)->find();
    }

    public function test_list_throws_rm_api_list_failed_exception_on_non_zero_exit(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stderr: 'rmapi: something unknown bad happened', exitCode: 1));

        $this->expectException(RMApiListFailedException::class);
        $this->makeRMapi($runner)->list('/');
    }

    public function test_find_builds_flags_in_order(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'find', '--starred', '--tag=work', '--tag=todo', '/', 'notes'])
            ->willReturn($this->processOutput(stdout: '[]'));

        $this->makeRMapi($runner)->find(query: 'notes', starred: true, tags: ['work', 'todo']);
    }

    public function test_find_parses_json_from_stdout_only(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(
                stdout: '[{"id":"abc","name":"Notes","type":"DocumentType"}]',
                stderr: 'Refreshing tree...',
            ));

        $items = $this->makeRMapi($runner)->find();

        $this->assertCount(1, $items);
        $this->assertSame('Notes', $items->first()['name']);
    }

    public function test_find_throws_rm_api_find_failed_exception_on_non_zero_exit(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stderr: 'rmapi: something unknown bad happened', exitCode: 1));

        $this->expectException(RMApiFindFailedException::class);
        $this->makeRMapi($runner)->find();
    }

    public function test_get_by_id_passes_id_as_separate_argv_element(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'get', '--id', 'abc-123'])
            ->willReturn($this->processOutput(stderr: 'boom', exitCode: 1));

        // getById is meant to create files, we don't care about file operations so the fake binary fails
        $this->expectException(RMApiGetFailedException::class);
        $this->makeRMapi($runner)->getById('abc-123', 'Carol');
    }

    public function test_get_by_id_error_message_includes_rmapi_output(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stderr: 'rmapi: device offline', exitCode: 1));

        $this->expectException(RMApiGetFailedException::class);
        $this->expectExceptionMessage('device offline');

        $this->makeRMapi($runner)->getById('abc-123', 'Carol');
    }

    public function test_get_currently_shell_escapes_path(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(['--json', '-ni', 'get', '/Work/Carol'])
            ->willReturn($this->processOutput(stderr: 'boom', exitCode: 1));

        $this->expectException(RMApiGetFailedException::class);
        $this->makeRMapi($runner)->get('/Work/Carol');
    }

    public function test_get_error_message_includes_rmapi_output(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stderr: 'rmapi: connection refused', exitCode: 1));

        $this->expectException(RMApiGetFailedException::class);
        $this->expectExceptionMessage('connection refused');

        $this->makeRMapi($runner)->get('/Work/Carol');
    }

    public function test_get_throws_rm_api_zip_move_failed_exception_when_storage_move_fails(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stdout: '', exitCode: 0));

        $this->expectException(RMApiZipMoveFailedException::class);
        $this->makeRMapi($runner)->get('/Work/Carol');
    }

    public function test_refresh_throws_rm_api_refresh_failed_exception_on_failure(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stderr: 'rmapi: refresh failed', exitCode: 1));

        $this->expectException(RMApiRefreshFailedException::class);
        $this->expectExceptionMessage('refresh failed');

        $this->makeRMapi($runner)->refresh();
    }

    public function test_authenticate_passes_code_via_stdin_not_argv(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([], 'one-time-code')
            ->willReturn($this->processOutput(stdout: 'unrecognised output'));

        $this->expectException(RMApiUnknownAuthOutputException::class);
        $this->makeRMapi($runner)->authenticate('one-time-code');
    }

    public function test_authenticate_throws_rm_api_invalid_code_exception_when_output_says_incorrect(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stdout: 'incorrect code'));

        $this->expectException(RMApiInvalidCodeException::class);
        $this->makeRMapi($runner)->authenticate('bad-code');
    }

    public function test_authenticate_throws_rm_api_token_creation_failed_exception_when_output_says_device_token_failure(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stdout: 'failed to create a new device token'));

        $this->expectException(RMApiTokenCreationFailedException::class);
        $this->makeRMapi($runner)->authenticate('one-time-code');
    }

    public function test_authenticate_throws_rm_api_unknown_auth_output_exception_when_output_is_unrecognized(): void
    {
        $runner = $this->createMock(RMapiProcessRunner::class);
        $runner->method('run')
            ->willReturn($this->processOutput(stdout: 'gibberish nobody understands'));

        $this->expectException(RMApiUnknownAuthOutputException::class);
        $this->makeRMapi($runner)->authenticate('one-time-code');
    }
}
