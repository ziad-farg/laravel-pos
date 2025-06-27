<?php

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
