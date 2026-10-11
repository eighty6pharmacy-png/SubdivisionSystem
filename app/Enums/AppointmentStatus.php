<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'Pending';
    case Scheduled = 'Scheduled';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
