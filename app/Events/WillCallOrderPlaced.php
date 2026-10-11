<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Will-call pickup workflow event (ai/modules/will-call-notifications).
 * Notification listeners are attached in phase 4.
 */
class WillCallOrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
