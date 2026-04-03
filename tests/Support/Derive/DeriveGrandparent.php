<?php

namespace Tests\Support\Derive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeriveGrandparent extends Model
{
    use HasFactory;

    protected $table = 'derive_test_grandparents';
    protected $guarded = [];

    protected static function newFactory(): DeriveGrandparentFactory
    {
        return new DeriveGrandparentFactory();
    }
}