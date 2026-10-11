<?php

namespace App\Enums;

enum FulfillmentMethod: string
{
    case Ship = 'ship';
    case WillCall = 'will_call';

    public function label(): string
    {
        return match ($this) {
            self::Ship => 'Ship',
            self::WillCall => 'Will Call',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ship => 'gray',
            self::WillCall => 'info',
        };
    }
}
