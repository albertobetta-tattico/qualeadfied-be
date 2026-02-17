<?php

namespace App\Enums;

enum PurchaseMode: string
{
    case Exclusive = 'exclusive';
    case Shared = 'shared';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
