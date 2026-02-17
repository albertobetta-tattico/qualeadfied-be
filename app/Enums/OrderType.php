<?php

namespace App\Enums;

enum OrderType: string
{
    case Single = 'single';
    case Package = 'package';
    case FreeTrial = 'free_trial';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
