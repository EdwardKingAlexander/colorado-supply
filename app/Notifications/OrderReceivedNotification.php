<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\DescribesOrders;

/** Bell entry confirming an order (the confirmation email is sent at checkout). */
class OrderReceivedNotification extends AppNotification
{
    use DescribesOrders;

    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return NotificationType::OrderReceived;
    }

    public function title(object $notifiable): string
    {
        return "Order {$this->order->order_number} received";
    }

    public function body(object $notifiable): string
    {
        return $this->order->isWillCall()
            ? "We'll let you know when it's ready for pickup at {$this->pickupName($this->order)}."
            : "We'll let you know when it ships.";
    }

    public function url(object $notifiable): ?string
    {
        return $this->trackerUrl($this->order);
    }

    protected function extraData(object $notifiable): array
    {
        return $this->orderExtraData($this->order);
    }
}
