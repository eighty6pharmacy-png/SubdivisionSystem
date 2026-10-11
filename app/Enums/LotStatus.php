<?php

namespace App\Enums;

enum LotStatus: string
{
    case Available = 'Available';
    case Reserved = 'Reserved';
    case Occupied = 'Occupied';
    case VacantHouse = 'Vacant House';
    case VacantLot = 'Vacant Lot';
    case UnderConstruction = 'Under Construction';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
