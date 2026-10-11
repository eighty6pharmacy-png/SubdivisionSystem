<?php

namespace App\Enums;

enum VisitorStatus: string
{
    case Pending = 'Pending';
    case Approved = 'Approved';
    case Entered = 'Entered';
    case Rejected = 'Rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
