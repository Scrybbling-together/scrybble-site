<?php

namespace App\Jobs;

use App\DataClasses\SyncContext;
use App\Events\RemarkableFileDownloadedEvent;
use App\Services\RMapi;
use Eloquent\Pathogen\Exception\EmptyPathException;
use Eloquent\Pathogen\Exception\NonAbsolutePathException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class DownloadRemarkableFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public SyncContext $sync_context) {}

    public function handle(): void
    {
        $RMapi = new RMapi($this->sync_context->user);
        $RMapi->refresh(strategy: 'hard');
        $this->sync_context->logStep('Downloading file', [
            'filename' => $this->sync_context->input_filename,
            'rm_file_id' => $this->sync_context->rm_file_id,
        ]);
        try {
            if ($this->sync_context->rm_file_id !== null) {
                // Use ID-based download
                $results = $RMapi->getById(
                    $this->sync_context->rm_file_id,
                    $this->sync_context->input_filename
                );
            } else {
                // Use legacy path-based download
                $results = $RMapi->get($this->sync_context->input_filename);
            }
        } catch (EmptyPathException|NonAbsolutePathException $e) {
            $this->sync_context->logError('Failed downloading file, looks like the input path is incorrect');

            return;
        } catch (FileNotFoundException $e) {
            $this->sync_context->logError($e->getMessage());

            return;
        } catch (RuntimeException $e) {
            $this->sync_context->logError("Failed downloading file, unknown error (as of yet): {$e->getMessage()}");

            return;
        }
        $output = $results['output'];
        $this->sync_context->downloaded_zip_location = $results['downloaded_zip_location'];
        $this->sync_context->folder = $results['folder'];
        $this->sync_context->logStep("Remarks API finished: `$output`", $this->sync_context->toArray());

        event(new RemarkableFileDownloadedEvent($this->sync_context));
    }
}
