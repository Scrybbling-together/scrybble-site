<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @link https://help.gumroad.com/article/76-license-keys
 */
class GumroadLicense extends Model
{
    protected $fillable = [
        'valid',
        'license',
    ];
}
