<?php

namespace App\Enums;

enum SubscriptionPeriod: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case EveryTwoYears = 'every_two_years';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
            self::EveryTwoYears => 'Two-yearly',
        };
    }
}