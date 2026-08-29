<?php

namespace App\DataClasses;

use App\Models\Sync;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class SyncContext implements Arrayable
{
    public string $sync_id;

    public Sync $sync;

    public mixed $folder;

    public string $downloaded_zip_location;

    public function __construct(
        public string $input_filename,
        public User $user,
        public ?string $rm_file_id = null
    ) {
        $this->sync_id = Str::random();

        $sync = new Sync;
        $sync->filename = $this->input_filename;
        $sync->sync_id = $this->sync_id;
        $sync->user()->associate($user);
        $sync->save();
        $this->sync = $sync;
    }

    public function logStep(string $string, array $context = []): void
    {
        $log = new SyncLog;
        $log->message = $string;
        $log->severity = 'info';
        if (count($context)) {
            $log->context = $context;
        }
        $log->belongsToSync()->associate($this->sync);
        $log->save();
    }

    public function logError(string $message, array $context = []): void
    {
        $log = new SyncLog;
        $log->message = $message;
        $log->severity = 'error';
        if (count($context)) {
            $log->context = $context;
        }
        $log->belongsToSync()->associate($this->sync);
        $log->save();
    }

    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'sync_id' => $this->sync_id,
            'input_filename' => $this->input_filename,
            'zip' => $this->downloaded_zip_location,
            'folder' => $this->folder,
        ];
    }
}
