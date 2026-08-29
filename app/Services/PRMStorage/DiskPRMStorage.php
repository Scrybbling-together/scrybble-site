<?php

namespace App\Services\PRMStorage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DiskPRMStorage implements PRMStorageInterface
{
    private FilesystemAdapter|Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('efs');
    }

    public function store(string $path, string $prmFileContents): void
    {
        $success = $this->disk->put($path, $prmFileContents);
        if (! $success) {
            throw new RuntimeException('Failed to store PRM on disk');
        }
    }

    public function getDownloadURL(string $path): string
    {
        return $this->disk->temporaryUrl($path, now()->addMinutes(5));
    }
}
