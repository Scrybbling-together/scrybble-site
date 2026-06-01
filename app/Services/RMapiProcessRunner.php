<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\UserStorage;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

/**
 * Tenant-scoped runner for the bundled `rmapi` binary.
 */
readonly class RMapiProcessRunner
{
    public function __construct(
        private string $binaryPath,
        private string $configPath,
        private string $cacheHome,
        private string $workingDir,
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
     */
    public function run(array $argv, ?string $stdin = null, int $timeoutSeconds = 60): RMapiProcessOutput
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

        // Capture stdout, stderr, and the combined chronological stream in one pass.
        $combined = '';
        $stdout = '';
        $stderr = '';
        $process->run(function (string $type, string $buffer) use (&$combined, &$stdout, &$stderr): void {
            $combined .= $buffer;
            if ($type === Process::OUT) {
                $stdout .= $buffer;
            } else {
                $stderr .= $buffer;
            }
        });

        $exit = $process->getExitCode();
        if ($exit >= 128) {
            $exit -= 128;
        }
        if ($exit === SIGPIPE) {
            $exit = 0;
        }

        return new RMapiProcessOutput(
            combined: $combined,
            stdout:   $stdout,
            stderr:   $stderr,
            exitCode: $exit,
        );
    }
}
