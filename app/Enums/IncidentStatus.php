<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Progress = 'progress';
    case Resolved = 'resolved';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
