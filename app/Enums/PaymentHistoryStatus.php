<?php

namespace App\Enums;

enum PaymentHistoryStatus: string
{
    case Paid = 'Paid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
