<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $casts = [
        'context' => 'json',
    ];

    public function belongsToSync(): BelongsTo
    {
        return $this->belongsTo(Sync::class, 'sync_id', 'id');
    }
}
