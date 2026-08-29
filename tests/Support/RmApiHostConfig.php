<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Config\Repository;

final class RmApiHostConfig
{
    /**
     * Point the scrybble config at a given RMFAKECLOUD_HOST value by
     * (re)loading the config file, which re-reads the env var.
     */
    public static function set(Repository $config, ?string $host): void
    {
        if ($host === null) {
            unset($_ENV['RMFAKECLOUD_HOST'], $_SERVER['RMFAKECLOUD_HOST']);
        } else {
            $_ENV['RMFAKECLOUD_HOST'] = $host;
            $_SERVER['RMFAKECLOUD_HOST'] = $host;
        }

        $config->set('scrybble', require base_path('config/scrybble.php'));
    }
}
