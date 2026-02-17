<?php

namespace App\Enums;

enum AdminStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
