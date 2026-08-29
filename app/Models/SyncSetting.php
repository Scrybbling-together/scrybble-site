<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncSetting extends Model
{
    protected $fillable = ['filename', 'user_id', 'highlightsToText'];
}
