<?php

namespace App\Enums;

enum DownpaymentStatus: string
{
    case Pending = 'Pending';
    case GoodStanding = 'Good Standing';
    case FullyPaid = 'Fully Paid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
