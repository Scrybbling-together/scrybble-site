<?php
declare(strict_types=1);

namespace App\Services;

use App\Events\ReMarkableAuthenticatedEvent;
use App\Exceptions\MissingRMApiAuthenticationTokenException;
use App\Exceptions\RMApiNonZeroStatusCodeException;
use App\Helpers\UserStorage;
use App\Models\User;
use App\Support\RmAuthenticationFile;
use Eloquent\Pathogen\AbsolutePath;
use Eloquent\Pathogen\Exception\EmptyPathException;
use Eloquent\Pathogen\Exception\NonAbsolutePathException;
use Eloquent\Pathogen\Path;
use Eloquent\Pathogen\PathInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;
use Sentry;
use Symfony\Component\Process\Process;

/**
 *
 */
class RMapi
{
    private Filesystem $storage;
    private int $userId;

    public function __construct(User $user = null)
    {
        $user1 = $user ?? Auth::user();
        $this->storage = UserStorage::get($user1);
        $this->userId = $user1->id;
    }

    /**
     * @return bool
     * @throws MissingRMApiAuthenticationTokenException
     */
    public function isAuthenticated(): bool
    {
        $authFile = new RmAuthenticationFile($this->storage);
        if (!$authFile->exists()) {
            return false;
        }

        if ($authFile->hasValidAuthenticationValues()) {
            return true;
        } else {
            throw new MissingRMApiAuthenticationTokenException();
        }
    }

    /**
     * @param array $command
     * @return array
     */
    #[ArrayShape([Collection::class, 'int'])]
    public function executeRMApiCommand(array $command): array
    {
        [$outputLines, $exit_code] = $this->execWithProcess(
            arguments: array_merge(['--json', '-ni'], $command),
            workingDirectory: $this->storage->path('')
        );

        $output = collect($outputLines)->filter(function ($line) {
            if (Str::startsWith($line, 'Refreshing tree')) {
                return false;
            }
            if (Str::startsWith($line, 'WARNING')) {
                return false;
            }
            if (Str::contains($line, 'Using the new 1.5 sync')) {
                return false;
            }
            if (Str::contains($line, 'Make sure you have a backup')) {
                return false;
            }
            return true;
        });

        return [$output, $exit_code];
    }

    /**
     * Execute rmapi command using Symfony Process
     *
     * @param array $arguments Additional arguments to pass to rmapi
     * @param string|null $input Input to pass via stdin
     * @param string|null $workingDirectory Working directory for the process
     * @return array [output_lines, exit_code]
     */
    private function execWithProcess(
        ?array   $arguments = null,
        ?string $input = null,
        ?string $workingDirectory = null
    ): array
    {
        $rmapi = base_path('binaries/rmapi');

        // Build the process with rmapi + any additional arguments
        if ($arguments !== null) {
            $process = new Process(array_merge([$rmapi], $arguments));
        } else {
            $process = new Process([$rmapi]);
        }
        $process->setEnv([
            'RMAPI_CONFIG' => $this->storage->path('.rmapi-auth'),
            'XDG_CACHE_HOME' => $this->storage->path(''),
        ]);
        $process->setTimeout(60);

        if ($input !== null) {
            $process->setInput($input);
        }

        if ($workingDirectory !== null) {
            $process->setWorkingDirectory($workingDirectory);
        }

        // Capture both stdout and stderr chronologically
        $combinedOutput = [];
        $process->run(function ($type, $buffer) use (&$combinedOutput) {
            $combinedOutput[] = $buffer;
        });

        $exit_code = $process->getExitCode();

        // Handle SIGPIPE like the original
        if ($exit_code >= 128) {
            $exit_code -= 128;
        }
        if ($exit_code === SIGPIPE) {
            $exit_code = 0;
        }

        // Split combined output into lines
        $allOutput = implode('', $combinedOutput);
        $outputLines = explode("\n", $allOutput);

        return [$outputLines, $exit_code];
    }


    public function authenticate(string $code): bool
    {
        [$outputLines, $exit_code] = $this->execWithProcess(input: $code);
        $command_output = implode("\n", $outputLines);

        $index = Str::lower($command_output);
        if (Str::contains($index, 'refresh') || Str::contains($index, "syncversion: 1.5")) {
            event(new ReMarkableAuthenticatedEvent());
            return true;
        }
        if (Str::contains($index, 'incorrect') || Str::contains($index, "enter one-time code")) {
            throw new InvalidArgumentException('Invalid code');
        }
        if (Str::contains($index, 'failed to create a new device token')) {
            throw new RuntimeException('Failed to create token');
        }
        if ($exit_code !== 0) {
            Sentry::captureException(new RMApiNonZeroStatusCodeException(
                'authenticate',
                $exit_code,
                explode("\n", $command_output)
            ));
        }
        throw new RuntimeException('unknown error');
    }

    /**
     * Search for files recursively using rmapi find command.
     */
    public function find(?string $query = null, ?bool $starred = null, array $tags = []): Collection
    {
        $commandParts = ['find'];

        if ($starred === true) {
            $commandParts[] = '--starred';
        }

        foreach ($tags as $tag) {
            $commandParts[] = "--tag=$tag";
        }

        $commandParts[] = '/';

        if ($query !== null && $query !== '') {
            $commandParts[] = $query;
        }

        [$output, $exit_code] = $this->executeRMApiCommand($commandParts);

        if ($exit_code !== 0) {
            $error = implode("\n", $output->toArray());
            throw new RuntimeException("rmapi find failed with exit code `$exit_code`: " . $error);
        }

        $jsonOutput = $output->implode("");
        $nodes = json_decode($jsonOutput, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Failed to parse rmapi JSON output: " . json_last_error_msg());
        }

        return collect($nodes)->map(function (array $node) {
            $type = match ($node['type']) {
                'CollectionType' => 'd',
                'DocumentType', 'TemplateType' => 'f',
                default => 'f',
            };

            return [
                'type' => $type,
                'name' => $node['name'],
                'path' => '/' . $node['name'],
                'id' => $node['id'],
                'version' => $node['version'] ?? null,
                'modifiedClient' => $node['modifiedClient'] ?? null,
                'currentPage' => $node['currentPage'] ?? null,
                'tags' => $node['tags'] ?? [],
                'starred' => $node['starred'] ?? false,
            ];
        })->filter(fn($item) => $item['type'] === 'f')
            ->values();
    }

    /**
     *
     */
    public function list(string $path = '/'): Collection
    {
        [$output, $exit_code] = $this->executeRMApiCommand(['ls', $path]);

        if ($exit_code !== 0) {
            $error = implode("\n", $output->toArray());
            throw new RuntimeException("rmapi ls path failed with exit code `$exit_code`: " . $error);
        }

        $jsonOutput = $output->implode("");
        $nodes = json_decode($jsonOutput, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Failed to parse rmapi JSON output: " . json_last_error_msg());
        }

        return collect($nodes)->map(function (array $node) use ($path) {
            $type = match ($node['type']) {
                'CollectionType' => 'd',
                'DocumentType', 'TemplateType' => 'f',
                default => 'f',
            };
            $name = $node['name'];

            return [
                'type' => $type,
                'name' => $name,
                'path' => $type === 'd' ? "$path$name/" : "$path$name",
                'id' => $node['id'],
                'version' => $node['version'] ?? null,
                'modifiedClient' => $node['modifiedClient'] ?? null,
                'currentPage' => $node['currentPage'] ?? null,
                'tags' => $node['tags'] ?? [],
                'starred' => $node['starred'] ?? false,
            ];
        })->sort(function ($a, $b) {
            // Folders first, then files, alphabetically within each group
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'd' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        })->values();
    }

    /**
     * @param $strategy string Either "hard" or "soft". Hard removes the cache on disk, soft calls the "refresh" api in rmapi
     * @return bool
     */
    public function refresh(string $strategy = "soft"): bool
    {
        $hardRefresh = fn() => $this->storage->delete("rmapi/tree.cache");
        $softRefresh = function () {
            [$refresh_output, $refresh_exit_code] = $this->executeRMApiCommand(['refresh']);
            if ($refresh_exit_code !== 0) {
                $all_refresh_output = implode("\n", $refresh_output);
                throw new RuntimeException("Failed to refresh: `$all_refresh_output`");
            }
        };

        $redis = Redis::client();
        $key = "rmapi:lastRefreshed:$this->userId";
        $ttl = $redis->ttl($key);

        if ($ttl === -1 || $ttl === -2) {
            if ($strategy === "soft") {
                $softRefresh();
            } elseif ($strategy === "hard") {
                $hardRefresh();
            }
            $redis->set($key, "", [// 120 seconds
                "EX" => 120]);
            return true;
        }

        return false;
    }

    public static function hashedFilepath(string $filePath): string
    {
        return hash('sha1', $filePath) . ".zip";
    }

    /**
     * @throws EmptyPathException
     * @throws NonAbsolutePathException
     * @throws RuntimeException
     * @throws FileNotFoundException
     */
    public function get(string $filePath): array
    {
        // rmapi runs in Process array mode, so a leading "-" in the path would be
        // parsed as a flag. reMarkable paths are absolute; reject anything else.
        $folders = AbsolutePath::fromString($filePath);

        [$output, $exit_code] = $this->executeRMApiCommand(['get', $filePath]);
        if ($exit_code !== 0) {
            if ($output && Str::contains($output->implode(""), "file doesn't exist")) {
                throw new FileNotFoundException("Failed downloading file, it doesn't seem to exist (have you deleted the file? Otherwise try resyncing the file on your device)");
            }
            throw new RuntimeException('RMapi `get` command failed for an unknown reason');
        }
        $location = $this->getDownloadedZipLocation($filePath)->toRelative();

        $newLocation = static::hashedFilepath($filePath);
        if (!$this->storage->move($location, $newLocation)) {
            throw new RuntimeException("Unable to rename downloaded RMZip to hashed filePath " . $location . " to " . $newLocation);
        }

        return ['output' => $output, 'downloaded_zip_location' => $newLocation, 'folder' => $folders->replaceName("")->string()];
    }

    /**
     * Download a file by its reMarkable document ID.
     *
     * @throws RuntimeException
     * @throws FileNotFoundException
     */
    public function getById(string $rmFileId, string $name): array
    {
        [$output, $exit_code] = $this->executeRMApiCommand(['get', '--id', $rmFileId]);
        if ($exit_code !== 0) {
            if ($output && Str::contains($output->implode(""), "doesn't exist")) {
                throw new FileNotFoundException("Failed downloading file, it doesn't seem to exist (have you deleted the file? Otherwise try resyncing the file on your device)");
            }
            throw new RuntimeException('RMapi `get --id` command failed for an unknown reason');
        }

        // Downloaded file is named after the document name, not the ID
        $location = $this->getDownloadedZipLocation($name)->toRelative();

        // Hash based on ID for uniqueness
        $newLocation = static::hashedFilepath($rmFileId);
        if (!$this->storage->move($location, $newLocation)) {
            throw new RuntimeException("Unable to rename downloaded RMZip to hashed filePath " . $location . " to " . $newLocation);
        }

        // For ID-based downloads, we don't have the folder path
        return ['output' => $output, 'downloaded_zip_location' => $newLocation, 'folder' => '/'];
    }

    /**
     * @return void
     */
    public function configureEnv(): array
    {
        putenv('RMAPI_CONFIG=' . $this->storage->path('.rmapi-auth'));
        putenv('XDG_CACHE_HOME=' . $this->storage->path(''));

        return [

        ];
    }


    /**
     * @param string $rmapiDownloadPath
     * @return PathInterface
     */
    private function getDownloadedZipLocation(string $rmapiDownloadPath): PathInterface
    {
        $filename = Path::fromString($rmapiDownloadPath)->name();
        return Path::fromString($filename)->joinExtensions('rmdoc');
    }
}
