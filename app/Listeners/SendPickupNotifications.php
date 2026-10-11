<?php

namespace App\Listeners;

use App\Events\OrderPickedUp;
use App\Events\OrderReadyForPickup;
use App\Notifications\PickedUpNotification;
use App\Notifications\ReadyForPickupNotification;
use App\Support\OrderNotifier;

/**
 * Tells the buyer when their will call order is ready and when it was
 * collected (ai/modules/will-call-notifications, phase 4).
 */
class SendPickupNotifications
{
    public function handleReady(OrderReadyForPickup $event): void
    {
        $order = $event->order->loadMissing('pickupLocation');

        OrderNotifier::send($order, new ReadyForPickupNotification($order));
    }

    public function handlePickedUp(OrderPickedUp $event): void
    {
        OrderNotifier::send($event->order, new PickedUpNotification($event->order));
    }
}
