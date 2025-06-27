<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
