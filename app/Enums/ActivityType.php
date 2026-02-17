<?php

namespace App\Enums;

enum ActivityType: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Export = 'export';
    case Import = 'import';
    case StatusChange = 'status_change';
    case PasswordReset = 'password_reset';
    case ConfigChange = 'config_change';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
