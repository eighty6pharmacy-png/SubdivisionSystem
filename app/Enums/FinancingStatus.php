<?php

namespace App\Enums;

enum FinancingStatus: string
{
    case Pending = 'Pending';
    case Processing = 'Processing';
    case Released = 'Released';
    case Cleared = 'Cleared';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
