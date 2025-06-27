<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Completed = 'completed';
    case Fully_Returned = 'fully_Returned';
    case Partially_Returned = 'partially_Returned';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
