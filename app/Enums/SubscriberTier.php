<?php

namespace App\Enums;

enum SubscriberTier: string
{
    case Student = 'For students';
    case Professional = 'Professional tier';
    case Supporters = 'Supporters tier';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Professional => 'Professional',
            self::Supporters => 'Supporters',
        };
    }
}