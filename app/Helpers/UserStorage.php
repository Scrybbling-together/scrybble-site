<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Creates a Storage for an individual user based on their ID
 */
class UserStorage
{
    public static function get(User $user): Filesystem
    {
        $efs = Storage::disk('efs');
        $user_dir = "user-{$user->id}/";
        $storage = Storage::build($efs->path($user_dir));
        $storage->buildTemporaryUrlsUsing(
            fn ($path, $expiration, $options) => URL::temporarySignedRoute('prmdownload', $expiration, array_merge($options, ['path' => $path]))
        );
        if (! $storage->exists('')) {
            $storage->makeDirectory('');
        }

        return $storage;
    }
}
