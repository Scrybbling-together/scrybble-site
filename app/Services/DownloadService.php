<?php

namespace App\Services;

use App\Helpers\FileManipulations;
use App\Helpers\UserStorage;
use App\Models\User;
use Eloquent\Pathogen\RelativePath;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use UnexpectedValueException;

class DownloadService
{
    public function prepareRMNZipUrl(int $user_id, string $sync_id): string
    {
        $storage = Storage::disk('efs');

        FileManipulations::ensureDirectoryTreeExists($storage, new RelativePath(["user-{$user_id}", 'input_documents']));
        $rmNotebookPath = new RelativePath(["user-{$user_id}", 'input_documents', "$sync_id.rmn"]);

        if (! $storage->exists($rmNotebookPath->string())) {
            try {
                FileManipulations::zipDirectory($storage, new RelativePath(["user-{$user_id}", 'jobs', $sync_id, 'extractedFiles']), $rmNotebookPath);
            } catch (UnexpectedValueException) {
                throw new GoneHttpException('Input files do not exist anymore');
            }
        }

        return $storage->temporaryUrl($rmNotebookPath->string(), now()->addMinutes(5));
    }

    public function prepareProcessedRemarksZipUrl(int $user_id, string $sync_id): string
    {
        $storage = UserStorage::get(User::find($user_id));

        $path = "processed/{$sync_id}.zip";
        if ($storage->exists($path)) {
            return $storage->temporaryUrl($path, now()->addMinutes(5));
        }
        throw new GoneHttpException("File with sync id {$sync_id} has been deleted");
    }
}
