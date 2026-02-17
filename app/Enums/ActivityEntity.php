<?php

namespace App\Enums;

enum ActivityEntity: string
{
    case User = 'user';
    case Client = 'client';
    case Lead = 'lead';
    case Order = 'order';
    case Invoice = 'invoice';
    case Category = 'category';
    case Package = 'package';
    case Pricing = 'pricing';
    case Admin = 'admin';
    case System = 'system';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
