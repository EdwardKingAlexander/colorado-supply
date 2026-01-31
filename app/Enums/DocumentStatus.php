<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case PendingRenewal = 'pending_renewal';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::PendingRenewal => 'Pending Renewal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'danger',
            self::PendingRenewal => 'warning',
        };
    }
}
