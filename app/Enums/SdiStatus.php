<?php

namespace App\Enums;

enum SdiStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case NotDelivered = 'not_delivered';
    case Error = 'error';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
