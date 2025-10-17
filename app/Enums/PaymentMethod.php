<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Card = 'card';
    case Cash = 'cash';
    case Check = 'check';
    case Wire = 'wire';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Credit/Debit Card',
            self::Cash => 'Cash',
            self::Check => 'Check',
            self::Wire => 'Wire Transfer',
            self::Other => 'Other',
        };
    }
}
