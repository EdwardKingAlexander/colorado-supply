<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Fulfilled = 'fulfilled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Unfulfilled => 'Unfulfilled',
            self::PartiallyFulfilled => 'Partially Fulfilled',
            self::Fulfilled => 'Fulfilled',
            self::Returned => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unfulfilled => 'gray',
            self::PartiallyFulfilled => 'warning',
            self::Fulfilled => 'success',
            self::Returned => 'danger',
        };
    }
}
