<?php

namespace App\Listeners;

use App\Events\StoreOrderPlaced;
use App\Notifications\Admin\BackorderAlert;
use App\Notifications\Admin\NewOrderAlert;
use App\Notifications\OrderReceivedNotification;
use App\Services\Orders\StockShortfall;
use App\Support\AdminNotifier;
use App\Support\OrderNotifier;

/**
 * A store order was placed: confirm it in the buyer's bell, alert admins
 * (will call orders as their own type), and flag any backordered lines
 * (ai/modules/will-call-notifications-complete, phase 4).
 */
class SendOrderPlacedNotifications
{
    public function __construct(private StockShortfall $shortfall) {}

    public function handle(StoreOrderPlaced $event): void
    {
        $order = $event->order->loadMissing('items', 'pickupLocation');

        OrderNotifier::send($order, new OrderReceivedNotification($order));
        AdminNotifier::send(new NewOrderAlert($order));

        if ($lines = $this->shortfall->lines($order)) {
            AdminNotifier::send(new BackorderAlert($order, $lines));
        }
    }
}
