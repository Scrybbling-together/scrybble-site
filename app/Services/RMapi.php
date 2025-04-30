<?php
declare(strict_types=1);

namespace App\Services;

use App\Events\ReMarkableAuthenticatedEvent;
use App\Exceptions\MissingRMApiAuthenticationTokenException;
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
     * @param string $command
     * @return array
     */
    #[ArrayShape([Collection::class, 'int'])]
    public function executeRMApiCommand(string $command): array
    {
        $this->configureEnv();

        $rmapi = base_path('binaries/rmapi');
        $cwd_before = getcwd();
        if (!chdir($this->storage->path(''))) {
            throw new RuntimeException('Could not cd into userdir');
        }
        try {
            exec("$rmapi -ni $command 2>&1", $output, $exit_code);
        } finally {
            chdir($cwd_before);
        }

        $output = collect($output)->filter(function ($line) {
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
     * @param string $code
     * @return bool
     */
    public function authenticate(string $code): bool
    {
        $rmapi = base_path('binaries/rmapi');
        $this->configureEnv();
        $command = "echo $code | $rmapi";
        exec($command, $output, $exit_code);
        $command_output = implode("\n", $output);

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
            // unknown error for now
            $user = Auth::user()->id;
            Log::error("RMApi onetimecode failed for User#$user, exit_code=`$exit_code`, output=`$command_output`");
            $support_email = config('app.support_email');
            throw new RuntimeException("Unknown error, contact developer: $support_email");
        }
        throw new RuntimeException('unknown error');
    }

    /**
     *
     */
    public function list(string $path = '/'): Collection
    {
        [$output, $exit_code] = $this->executeRMApiCommand("ls \"$path\"");

        if ($exit_code !== 0) {
            $error = implode("\n", $output->toArray());

            if (Str::contains($error, "missing token")) {

            }

            throw new RuntimeException("rmapi ls path failed with exit code `$exit_code`: " . $error);
        }

        return $output->reduce(function (Collection $joinedLines, string $line) {
            if (Str::startsWith($line, ["[d]", "[f]"])) {
                $joinedLines->push($line);
            } else {
                $joinedLines[count($joinedLines) - 1] .= $line;
            }
            return $joinedLines;
        }, collect())->sort()->map(function ($line) use ($path) {
            // a $line can look like
            // [f] diary
            // [d] 2025
            // There is a rare edge-case where the filename is empty. Apparently this is possible in reMarkable.
            // [f]
            preg_match('/\[([df])]\s(.+)/', $line, $matches);
            $type = $matches[1];
            $filepath = $matches[2] ?? "";
            return ['type' => $type, 'name' => $filepath, 'path' => $type === 'd' ? "$path$filepath/" : "$path$filepath"];
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
            [$refresh_output, $refresh_exit_code] = $this->executeRMApiCommand("refresh");
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
        $rmapi_download_path = escapeshellarg($filePath);
        [$output, $exit_code] = $this->executeRMApiCommand("get $rmapi_download_path");
        if ($exit_code !== 0) {
            if ($output && Str::contains($output->implode(""), "file doesn't exist")) {
                throw new FileNotFoundException("Failed downloading file, it doesn't seem to exist (have you deleted the file? Otherwise try resyncing the file on your device)");
            }
            throw new RuntimeException('RMapi `get` command failed for an unknown reason');
        }
        $location = $this->getDownloadedZipLocation($filePath)->toRelative();

        $folders = AbsolutePath::fromString($filePath);

        $newLocation = static::hashedFilepath($filePath);
        if (!$this->storage->move($location, $newLocation)) {
            throw new RuntimeException("Unable to rename downloaded RMZip to hashed filePath " . $location . " to " . $newLocation);
        }

        return ['output' => $output, 'downloaded_zip_location' => $newLocation, 'folder' => $folders->replaceName("")->string()];
    }

    /**
     * @return void
     */
    public function configureEnv(): void
    {
        putenv('RMAPI_CONFIG=' . $this->storage->path('.rmapi-auth'));
        putenv('XDG_CACHE_HOME=' . $this->storage->path(''));
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
