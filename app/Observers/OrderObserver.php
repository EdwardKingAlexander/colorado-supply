<?php

namespace App\Observers;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;
use App\Support\OrderNotifier;

class OrderObserver
{
    /**
     * Notify the buyer on meaningful status transitions. Watches `updated`
     * only, so factory/seeder creates never send anything. When several
     * status fields change in one save, exactly one notification is sent —
     * the most significant transition wins.
     */
    public function updated(Order $order): void
    {
        $transition = $this->mostSignificantTransition($order);

        if ($transition === null) {
            return;
        }

        OrderNotifier::send($order, new OrderStatusUpdated($order, $transition));
    }

    protected function mostSignificantTransition(Order $order): ?string
    {
        if ($order->wasChanged('status') && $order->status === OrderStatus::Cancelled) {
            return 'cancelled';
        }

        if ($order->wasChanged('payment_status') && $order->payment_status === PaymentStatus::Refunded) {
            return 'refunded';
        }

        if ($order->wasChanged('fulfillment_status') && $order->fulfillment_status === FulfillmentStatus::Fulfilled) {
            return 'fulfilled';
        }

        return null;
    }
}
