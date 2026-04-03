<?php

namespace Tests\Support\Derive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeriveChild extends Model
{
    protected $table = 'derive_test_children';
    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DeriveParent::class, 'parent_id');
    }
}