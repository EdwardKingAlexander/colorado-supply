<?php

namespace App\Enums;

enum LinkCategory: string
{
    case Federal = 'federal';
    case State = 'state';
    case Local = 'local';
    case Vendor = 'vendor';
    case Banking = 'banking';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Federal => 'Federal',
            self::State => 'State',
            self::Local => 'Local',
            self::Vendor => 'Vendor',
            self::Banking => 'Banking',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Federal => 'primary',
            self::State => 'info',
            self::Local => 'success',
            self::Vendor => 'warning',
            self::Banking => 'gray',
            self::Other => 'gray',
        };
    }
}
