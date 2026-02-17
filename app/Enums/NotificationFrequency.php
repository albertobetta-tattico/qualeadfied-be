<?php

namespace App\Enums;

enum NotificationFrequency: string
{
    case Instant = 'instant';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Disabled = 'disabled';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
