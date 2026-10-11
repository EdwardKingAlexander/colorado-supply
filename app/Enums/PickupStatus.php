<?php

namespace App\Enums;

enum PickupStatus: string
{
    case AwaitingPick = 'awaiting_pick';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPick => 'Awaiting Pick',
            self::ReadyForPickup => 'Ready for Pickup',
            self::PickedUp => 'Picked Up',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AwaitingPick => 'warning',
            self::ReadyForPickup => 'info',
            self::PickedUp => 'success',
        };
    }
}
