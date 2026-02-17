<?php

namespace App\Enums;

enum AcquisitionMode: string
{
    case Exclusive = 'exclusive';
    case Shared = 'shared';
    case Free = 'free';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
