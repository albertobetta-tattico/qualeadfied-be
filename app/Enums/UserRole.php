<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
