<?php

namespace Tests\Support\Derive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeriveParent extends Model
{
    use HasFactory;

    protected $table = 'derive_test_parents';
    protected $guarded = [];

    public function grandparent(): BelongsTo
    {
        return $this->belongsTo(DeriveGrandparent::class, 'grandparent_id');
    }

    protected static function newFactory(): DeriveParentFactory
    {
        return new DeriveParentFactory();
    }
}