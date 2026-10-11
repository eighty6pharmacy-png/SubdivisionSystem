<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'Pending';
    case Reserved = 'Reserved';
    case Converted = 'Converted';
    case Cancelled = 'Cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuses that hold a lot; at most one such reservation may exist per lot.
     */
    public static function activeValues(): array
    {
        return [self::Pending->value, self::Reserved->value, self::Converted->value];
    }
}
