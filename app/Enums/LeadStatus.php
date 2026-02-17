<?php

namespace App\Enums;

enum LeadStatus: string
{
    case Free = 'free';
    case SoldExclusive = 'sold_exclusive';
    case SoldShared = 'sold_shared';
    case Exhausted = 'exhausted';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
