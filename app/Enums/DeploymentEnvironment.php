<?php

namespace App\Enums;

use RuntimeException;

enum DeploymentEnvironment: string
{
    case SelfHosted = 'self-hosted';
    case Commercial = 'commercial';

    public function isCommercial(): bool
    {
        return $this === self::Commercial;
    }

    public function isSelfHosted(): bool
    {
        return $this === self::SelfHosted;
    }

    public static function current(): self
    {
        $value = config('scrybble.deployment_environment');

        return self::tryFrom($value) ?? throw new RuntimeException(
            "Invalid SCRYBBLE_DEPLOYMENT_ENVIRONMENT value: '{$value}'. "
            ."Expected 'self-hosted' or 'commercial'. Check your .env file."
        );
    }
}
