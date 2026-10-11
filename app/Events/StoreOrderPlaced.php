<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A buyer placed an order at store checkout, shipped or will call
 * (ai/modules/will-call-notifications).
 */
class StoreOrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
