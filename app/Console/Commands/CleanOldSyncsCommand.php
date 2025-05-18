<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;

class CleanOldSyncsCommand extends Command
{
    protected $signature = 'app:clean-old-syncs {--f|force : Run without confirmation prompt}';

    protected $description = 'Cleans old syncs from the EFS storage';

    public function handle(): void
    {
        $storage = Storage::disk('efs');
        $efsPath = $storage->path('');
        $force = $this->option('force');

        // Calculate initial disk usage
        $initialUsage = $this->getDiskUsage($efsPath);
        $this->info("Initial disk usage: " . Helper::formatMemory($initialUsage));

        // Find old job directories
        $oldJobDirs = $this->findOldJobDirectories($storage);
        $jobDirectoriesToClean = count($oldJobDirs);

        // Find old processed files
        $oldProcessedFiles = $this->findOldProcessedFiles($storage);
        $filesToClean = count($oldProcessedFiles);

        $this->info("Found {$jobDirectoriesToClean} directories and {$filesToClean} files to clean up.");

        // Skip confirmation if force option is used
        if (!$force && !$this->confirm('Do you want to proceed with cleanup?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Clean up directories using Laravel's built-in methods
        $this->info("Cleaning up old job directories...");
        foreach ($oldJobDirs as $oldJobDir) {
            $relativePath = str_replace($efsPath, '', $oldJobDir->getRealPath());
            $storage->deleteDirectory($relativePath);
        }

        // Clean up processed files
        $this->info("Cleaning up old processed files...");
        foreach ($oldProcessedFiles as $file) {
            $relativePath = str_replace($efsPath, '', $file->getRealPath());
            $storage->delete($relativePath);
        }

        // Calculate final disk usage
        $finalUsage = $this->getDiskUsage($efsPath);
        $savedSpace = $initialUsage - $finalUsage;

        $this->info("Final disk usage: " . Helper::formatMemory($finalUsage));
        $this->info("Space saved: " . Helper::formatMemory($savedSpace));

        $this->info("Cleanup completed successfully.");
    }

    /**
     * Get disk usage in bytes
     */
    protected function getDiskUsage(string $path): int
    {
        $output = [];
        exec("du -sb " . escapeshellarg($path) . " | cut -f1", $output);
        return (int) ($output[0] ?? 0);
    }

    /**
     * @param Filesystem|FilesystemAdapter $storage
     * @return Finder
     */
    public function findOldJobDirectories(Filesystem|FilesystemAdapter $storage): Finder
    {
        $basePath = $storage->path('');
        $finder = new Finder();

        return $finder->directories()
            ->date('<' . Carbon::now()->subMonth()->format('Y-m-d'))
            ->depth(0)
            ->in(glob($basePath . '*/jobs') ?: []);
    }

    /**
     * @param Filesystem|FilesystemAdapter $storage
     * @return Finder
     */
    public function findOldProcessedFiles(Filesystem|FilesystemAdapter $storage): Finder
    {
        $basePath = $storage->path('');
        $finder = new Finder();

        return $finder->files()
            ->date('<' . Carbon::now()->subMonth()->format('Y-m-d'))
            ->depth(0)
            ->in(glob($basePath . '*/processed') ?: []);
    }
}
