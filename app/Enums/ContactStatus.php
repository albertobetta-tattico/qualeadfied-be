<?php

namespace App\Enums;

enum ContactStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case InProgress = 'in_progress';
    case NotInterested = 'not_interested';
    case Converted = 'converted';
    case Unreachable = 'unreachable';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
