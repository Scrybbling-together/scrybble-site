<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\UserStorage;
use App\Models\User;
use Symfony\Component\Process\Process;

/**
 * Tenant-scoped runner for the bundled `rmapi` binary.
 *
 * Constructed once per user (via {@see self::forUser()}); each {@see self::run()}
 * call only takes the command's argv (+ optional stdin/timeout). The tenant's
 * config path, cache home, and working directory are captured at construction.
 *
 * The binary is always invoked through Symfony Process in array mode, so argv
 * elements are passed as literal `argv[]` entries to `execvp(3)` — no shell
 * parses or unquotes them. Callers MUST NOT shell-escape arguments.
 */
// Not `final` so PHPUnit's createMock() can double it in tests; this is the
// only injection seam between RMapi and the rmapi binary.
class RMapiProcessRunner
{
    public function __construct(
        private readonly string $binaryPath,
        private readonly string $configPath,
        private readonly string $cacheHome,
        private readonly string $workingDir,
    ) {}

    public static function forUser(User $user): self
    {
        $storage = UserStorage::get($user);

        return new self(
            binaryPath: base_path('binaries/rmapi'),
            configPath: $storage->path('.rmapi-auth'),
            cacheHome:  $storage->path(''),
            workingDir: $storage->path(''),
        );
    }

    /**
     * Run rmapi with the given argv.
     *
     * @param list<string> $argv  Arguments after the binary. Pass as-is — array
     *                            mode means there is no shell to escape against.
     * @param string|null  $stdin Optional stdin payload (used by `authenticate`).
     * @return array{0: list<string>, 1: int} [outputLines, exitCode]
     */
    public function run(array $argv, ?string $stdin = null, int $timeoutSeconds = 60): array
    {
        $process = new Process([$this->binaryPath, ...$argv]);
        $process->setEnv([
            'RMAPI_CONFIG'   => $this->configPath,
            'XDG_CACHE_HOME' => $this->cacheHome,
        ]);
        $process->setWorkingDirectory($this->workingDir);
        $process->setTimeout($timeoutSeconds);

        if ($stdin !== null) {
            $process->setInput($stdin);
        }

        // Capture stdout+stderr chronologically into one buffer.
        $combined = [];
        $process->run(function ($_type, $buffer) use (&$combined) {
            $combined[] = $buffer;
        });

        $exit = $process->getExitCode();
        if ($exit >= 128) {
            $exit -= 128;
        }
        if ($exit === SIGPIPE) {
            $exit = 0;
        }

        return [explode("\n", implode('', $combined)), $exit];
    }
}
