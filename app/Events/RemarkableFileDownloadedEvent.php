<?php

namespace App\Events;

use App\DataClasses\SyncContext;
use Illuminate\Foundation\Events\Dispatchable;

class RemarkableFileDownloadedEvent
{
    use Dispatchable;

    public function __construct(public SyncContext $sync_context) {}
}
