<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DebugBundleAccessRequest;
use App\Models\Sync;
use App\Services\DownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DebugBundleController extends Controller
{
    public function __construct(
        private readonly DownloadService $downloadService,
    ) {}

    public function input(DebugBundleAccessRequest $request, Sync $sync): RedirectResponse
    {
        $url = $this->downloadService->prepareRMNZipUrl($sync->user_id, $sync->sync_id);

        return redirect($url);
    }

    public function full(DebugBundleAccessRequest $request, Sync $sync): BinaryFileResponse
    {
        $storage = Storage::disk('efs');
        $userId = $sync->user_id;
        $syncId = $sync->sync_id;

        $tempFile = tempnam(sys_get_temp_dir(), 'debug_bundle_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Add sync.json
        $zip->addFromString('sync.json', json_encode($sync->toArray(), JSON_PRETTY_PRINT));

        // Add logs.json
        $logs = $sync->logs()->get()->toArray();
        $zip->addFromString('logs.json', json_encode($logs, JSON_PRETTY_PRINT));

        // Add extractedFiles directory
        $extractedPath = "user-{$userId}/jobs/{$syncId}/extractedFiles";
        if ($storage->exists($extractedPath)) {
            $this->addDirectoryToZip($zip, $storage->path($extractedPath), 'extractedFiles');
        }

        // Add out directory
        $outPath = "user-{$userId}/jobs/{$syncId}/out";
        if ($storage->exists($outPath)) {
            $this->addDirectoryToZip($zip, $storage->path($outPath), 'out');
        }

        // Add processed.zip
        $processedPath = "user-{$userId}/processed/{$syncId}.zip";
        if ($storage->exists($processedPath)) {
            $zip->addFile($storage->path($processedPath), 'processed.zip');
        }

        // Add input.rmn
        $inputPath = "user-{$userId}/input_documents/{$syncId}.rmn";
        if ($storage->exists($inputPath)) {
            $zip->addFile($storage->path($inputPath), 'input.rmn');
        }

        $zip->close();

        return response()->download($tempFile, "debug-bundle-{$sync->sync_id}.zip", [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $zipPath): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $relativePath = $zipPath.'/'.substr($filePath, strlen($sourcePath) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
