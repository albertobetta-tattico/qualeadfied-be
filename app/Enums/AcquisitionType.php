<?php

namespace App\Enums;

enum AcquisitionType: string
{
    case Exclusive = 'exclusive';
    case Shared = 'shared';
    case FreeTrial = 'free_trial';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
