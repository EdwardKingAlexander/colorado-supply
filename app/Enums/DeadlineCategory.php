<?php

namespace App\Enums;

enum DeadlineCategory: string
{
    case Tax = 'tax';
    case LicenseRenewal = 'license_renewal';
    case Registration = 'registration';
    case Compliance = 'compliance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tax => 'Tax',
            self::LicenseRenewal => 'License Renewal',
            self::Registration => 'Registration',
            self::Compliance => 'Compliance',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Tax => 'danger',
            self::LicenseRenewal => 'warning',
            self::Registration => 'info',
            self::Compliance => 'primary',
            self::Other => 'gray',
        };
    }
}
