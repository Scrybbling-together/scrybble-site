<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemarkableDocumentShare extends Model
{
    use HasFactory;

    protected $table = 'remarkable_document_share';

    protected $fillable = [
        'feedback',
        'user_id',
        'sync_id',
        'developer_access_consent_granted',
        'open_access_consent_granted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sync(): BelongsTo
    {
        return $this->belongsTo(Sync::class);
    }
}
