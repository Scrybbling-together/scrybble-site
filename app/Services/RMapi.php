<?php
declare(strict_types=1);

namespace App\Services;

use App\Events\ReMarkableAuthenticatedEvent;
use App\Exceptions\MissingRMApiAuthenticationTokenException;
use App\Exceptions\RMApi\RMApiFindFailedException;
use App\Exceptions\RMApi\RMApiGetFailedException;
use App\Exceptions\RMApi\RMApiInvalidCodeException;
use App\Exceptions\RMApi\RMApiJsonParseException;
use App\Exceptions\RMApi\RMApiListFailedException;
use App\Exceptions\RMApi\RMApiRefreshFailedException;
use App\Exceptions\RMApi\RMApiTokenCreationFailedException;
use App\Exceptions\RMApi\RMApiUnknownAuthOutputException;
use App\Exceptions\RMApi\RMApiZipMoveFailedException;
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
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RMapi
{
    private Filesystem $storage;
    private int $userId;
    private RMapiProcessRunner $runner;

    public function __construct(?User $user = null, ?RMapiProcessRunner $runner = null)
    {
        $user = $user ?? Auth::user();
        $this->storage = UserStorage::get($user);
        $this->userId = $user->id;
        $this->runner = $runner ?? RMapiProcessRunner::forUser($user);
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
        }
        throw new MissingRMApiAuthenticationTokenException();
    }

    /**
     * @throws RMApiInvalidCodeException
     * @throws RMApiTokenCreationFailedException
     * @throws RMApiUnknownAuthOutputException
     */
    public function authenticate(string $code): bool
    {
        $result = $this->runner->run(argv: [], stdin: $code);

        $commandOutput = Str::lower($result->combined);
        if (Str::contains($commandOutput, 'refresh') || Str::contains($commandOutput, "syncversion: 1.5")) {
            event(new ReMarkableAuthenticatedEvent());
            return true;
        }
        if (Str::contains($commandOutput, 'incorrect') || Str::contains($commandOutput, "enter one-time code")) {
            throw new RMApiInvalidCodeException('Invalid code');
        }
        if (Str::contains($commandOutput, 'failed to create a new device token')) {
            throw new RMApiTokenCreationFailedException('Failed to create token');
        }
        throw new RMApiUnknownAuthOutputException("Authentication produced unrecognized output (exit code {$result->exitCode}): {$result->combined}");
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

        $result = $this->runner->run(array_merge(['--json', '-ni'], $commandParts));

        if ($result->exitCode !== 0) {
            throw new RMApiFindFailedException("rmapi find failed with exit code `{$result->exitCode}`: {$result->combined}");
        }

        $nodes = $this->parseJsonNodes($result->stdout);

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

    public function list(string $path = '/'): Collection
    {
        $result = $this->runner->run(['--json', '-ni', 'ls', $path]);

        if ($result->exitCode !== 0) {
            throw new RMApiListFailedException("rmapi ls path failed with exit code `{$result->exitCode}`: {$result->combined}");
        }

        $nodes = $this->parseJsonNodes($result->stdout);

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
            $result = $this->runner->run(['--json', '-ni', 'refresh']);
            if ($result->exitCode !== 0) {
                throw new RMApiRefreshFailedException("Failed to refresh: `{$result->combined}`");
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
            $redis->set($key, "", ["EX" => 120]);
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
     * @throws RMApiGetFailedException
     * @throws RMApiZipMoveFailedException
     * @throws FileNotFoundException
     */
    public function get(string $filePath): array
    {
        $folders = AbsolutePath::fromString($filePath);
        $result = $this->runner->run(['--json', '-ni', 'get', $filePath]);
        if ($result->exitCode !== 0) {
            if (Str::contains($result->combined, "file doesn't exist")) {
                throw new FileNotFoundException("Failed downloading file, it doesn't seem to exist (have you deleted the file? Otherwise try resyncing the file on your device)");
            }
            throw new RMApiGetFailedException("RMapi `get` command failed: {$result->combined}");
        }
        $location = $this->getDownloadedZipLocation($filePath)->toRelative();


        $newLocation = static::hashedFilepath($filePath);
        if (!$this->storage->move($location, $newLocation)) {
            throw new RMApiZipMoveFailedException("Unable to rename downloaded RMZip to hashed filePath " . $location . " to " . $newLocation);
        }

        return ['output' => $result->combined, 'downloaded_zip_location' => $newLocation, 'folder' => $folders->replaceName("")->string()];
    }

    /**
     * Download a file by its reMarkable document ID.
     *
     * @throws RMApiGetFailedException
     * @throws RMApiZipMoveFailedException
     * @throws FileNotFoundException|EmptyPathException
     */
    public function getById(string $rmFileId, string $name): array
    {
        $result = $this->runner->run(['--json', '-ni', 'get', '--id', $rmFileId]);
        if ($result->exitCode !== 0) {
            if (Str::contains($result->combined, "doesn't exist")) {
                throw new FileNotFoundException("Failed downloading file, it doesn't seem to exist (have you deleted the file? Otherwise try resyncing the file on your device)");
            }
            throw new RMApiGetFailedException("RMapi `get --id` command failed: {$result->combined}");
        }

        // Downloaded file is named after the document name, not the ID
        $location = $this->getDownloadedZipLocation($name)->toRelative();

        // Hash based on ID for uniqueness
        $newLocation = static::hashedFilepath($rmFileId);
        if (!$this->storage->move($location, $newLocation)) {
            throw new RMApiZipMoveFailedException("Unable to rename downloaded RMZip to hashed filePath " . $location . " to " . $newLocation);
        }

        // For ID-based downloads, we don't have the folder path
        return ['output' => $result->combined, 'downloaded_zip_location' => $newLocation, 'folder' => '/'];
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

    /**
     * @throws RMApiJsonParseException
     */
    private function parseJsonNodes(string $stdout): array
    {
        $nodes = json_decode($stdout, associative: true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RMApiJsonParseException("Failed to parse rmapi JSON output: " . json_last_error_msg());
        }
        return $nodes;
    }
}
