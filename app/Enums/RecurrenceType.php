<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case Once = 'once';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annually = 'annually';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'One Time',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annually => 'Annually',
            self::Custom => 'Custom',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Once => 'gray',
            self::Monthly => 'info',
            self::Quarterly => 'primary',
            self::Annually => 'success',
            self::Custom => 'warning',
        };
    }
}
