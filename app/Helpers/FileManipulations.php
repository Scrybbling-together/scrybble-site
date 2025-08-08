<?php
declare(strict_types=1);

namespace App\Helpers;

use Eloquent\Pathogen\Exception\InvalidPathStateException;
use Eloquent\Pathogen\Path;
use Eloquent\Pathogen\PathInterface;
use Eloquent\Pathogen\RelativePathInterface;
use FilesystemIterator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * A couple of high level file manipulation methods
 * All methods are written for usage with the Filesystem contract
 * and RelativePathInterfaces
 */
class FileManipulations {
    /**
     * @param Filesystem $storage
     * @param RelativePathInterface $filepath A path including a file at the end.
     * @return PathInterface Path relative to storage root (includes user-dir),
     *              excludes filename
     * @throws InvalidPathStateException
     */
    public static function ensureDirectoryTreeExists(Filesystem $storage, RelativePathInterface $filepath): PathInterface {
        $atoms = $filepath->atoms();

        // last atom is file
        $tree = Path::fromString('');
        foreach ($atoms as $directory_name) {
            $tree = $tree->joinAtoms($directory_name);
            $dir_path = $tree->toAbsolute();

            if (!$storage->exists($dir_path)) {
                $storage->makeDirectory($dir_path);
            }
        }
        return $tree;
    }

    /**
     * @param Filesystem $storage
     * @param RelativePathInterface $from
     * @param RelativePathInterface $to
     * @return void
     */
    public static function extractZip(Filesystem $storage, RelativePathInterface $from, RelativePathInterface $to): void {
        $zip = new ZipArchive();
        $result = $zip->open($storage->path($from));
        if ($result) {
            $extract_result = $zip->extractTo($storage->path($to));
            if (!$extract_result) {
                $zip->close();
                throw new RuntimeException('Unable to extract zip');
            }
        } else {
            throw new RuntimeException('Unable to open zip');
        }
    }

    /**
     * Zips the given directory, starts from $storage.
     * The files within the zip file start from $from
     * As if you do `ls $from | zip -` (not sure if that's legit bash)
     * @param Filesystem $storage
     * @param RelativePathInterface $from
     * @param RelativePathInterface $to
     * @return void
     */
    public static function zipDirectory(Filesystem $storage, RelativePathInterface $from, RelativePathInterface $to): void {
        $zip = new ZipArchive();
        $zip_location = $storage->path($to->string());
        if ($zip->open($zip_location, flags: ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Was unable to open zip at $zip_location");
        }

        $root = $storage->path($from);
        $dir_iter = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
        $iter = new RecursiveIteratorIterator($dir_iter);

        $remove_root = Path::fromString($storage->path($from))->normalize()->string();
        foreach ($iter as $info) {
            $path = $info->getPathname();
            // name inside zip, otherwise includes whole path like /var/www/html/.....
            $entry = Str::replace(search: $remove_root, replace: '', subject: $path);

            if (is_dir($path)) {
                $zip->addEmptyDir($path, $entry);
            } else if (is_file($path)) {
                $zip->addFile($path, $entry);
            }
        }

        if (!$zip->close()) {
            throw new RuntimeException('Was unable to close zip after creation');
        }
    }

    public static function moveFilesRecursively(Filesystem $sourceStorage, string $sourceDir, Filesystem $destStorage, string $destDir): void {
        if (!$destStorage->exists($destDir)) {
            throw new RuntimeException("Destination directory '{$destDir}' does not exist");
        }

        $files = $sourceStorage->allFiles($sourceDir);

        foreach ($files as $file) {
            $relativePath = substr($file, strlen($sourceDir) + 1);
            $destPath = $destDir . '/' . $relativePath;

            $destStorage->put($destPath, $sourceStorage->get($file));
        }
    }

    public static function verifyFilesMatch(Filesystem $sourceStorage, string $sourceDir, Filesystem $destStorage, string $destDir): bool {
        $sourceFiles = $sourceStorage->allFiles($sourceDir);
        $destFiles = $destStorage->allFiles($destDir);

        if (count($sourceFiles) !== count($destFiles)) {
            return false;
        }

        foreach ($sourceFiles as $sourceFile) {
            $relativePath = substr($sourceFile, strlen($sourceDir) + 1);
            $destPath = $destDir . '/' . $relativePath;

            if (!$destStorage->exists($destPath)) {
                return false;
            }

            if ($sourceStorage->size($sourceFile) !== $destStorage->size($destPath)) {
                return false;
            }
        }

        return true;
    }

    public static function removeDirectory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

}
