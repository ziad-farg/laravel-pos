<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Visa =  'visa';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
