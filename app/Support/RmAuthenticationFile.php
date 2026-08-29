<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;

class RmAuthenticationFile
{
    public function __construct(private FilesystemAdapter $filesystem) {}

    /**
     * Does the authentication file exist?
     */
    public function exists(): bool
    {
        return $this->filesystem->exists('.rmapi-auth');
    }

    /**
     * ```
     *  $ cat .rmapi-auth
     *  devicetoken: "...token"
     *  usertoken: "...token"
     * ```
     */
    public function hasValidAuthenticationValues(): bool
    {
        if ($this->exists()) {
            $contents = $this->filesystem->get('.rmapi-auth');
            $contents = trim($contents);
            // turn content into valid ini
            $contents = Str::replace(':', '=', $contents);

            $data = parse_ini_string($contents);

            $device_token = $data['devicetoken'] ?? '';
            $user_token = $data['usertoken'] ?? '';

            return strlen($device_token) > 0 && strlen($user_token) > 0;
        }

        return false;
    }
}
