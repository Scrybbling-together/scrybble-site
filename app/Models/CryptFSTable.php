<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptFSTable extends Model
{
    protected $table = 'crypt_file_system';

    protected $fillable = [
        'encryption_key_salt',
        'key_needs_derivation'
    ];

    protected function casts(): array
    {
        return [
            'key_needs_derivation' => 'boolean'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
