<?php
declare(strict_types=1);

namespace App\Services;

use Eloquent\Pathogen\RelativePathInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use JsonException;
use Str;

/**
 * Functionality directly related to the reMarkable itself
 * Primarily deals with extracting information out of rm notebook metadata
 */
class RemarkableService {

    /**
     * @throws JsonException
     */
    private function parseRMFile(string $type, Filesystem $userStorage, RelativePathInterface $to) {
        $filename = collect($userStorage->files($to))
            ->first(fn(string $filename) => Str::contains($filename, $type));

        return json_decode($userStorage->get($filename), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * The name of the reMarkable file
     * @param Filesystem $userStorage
     * @param RelativePathInterface $to
     * @return string
     * @throws JsonException
     */
    public function filename(Filesystem $userStorage, RelativePathInterface $to): string {
        return $this->metadata($userStorage, $to)['visibleName'];
    }

    /**
     * @param Filesystem $userStorage
     * @param RelativePathInterface $to
     * @return mixed
     * @throws JsonException
     */
    public function metadata(Filesystem $userStorage, RelativePathInterface $to): array {
        return $this->parseRMFile('.metadata', $userStorage, $to);
    }

    public function determineVersion(Filesystem $userStorage, RelativePathInterface $to): int {
        $content = $this->parseRMFile(".content", $userStorage, $to);

        return $content['formatVersion'] ?? 1;
    }
}
