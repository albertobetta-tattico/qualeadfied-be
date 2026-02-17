<?php

namespace App\Enums;

enum TransactionPaymentType: string
{
    case Card = 'card';
    case SepaDebit = 'sepa_debit';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
