<?php

namespace App\Enums;

enum BillStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
