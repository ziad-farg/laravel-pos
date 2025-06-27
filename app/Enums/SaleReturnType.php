<?php

namespace App\Enums;

enum SaleReturnType: string
{
    case FullReturn = 'full_return';
    case PartialReturn = 'partial_return';
}
