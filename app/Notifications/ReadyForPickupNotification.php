<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\DescribesOrders;

class ReadyForPickupNotification extends AppNotification
{
    use DescribesOrders;

    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return NotificationType::ReadyForPickup;
    }

    public function title(object $notifiable): string
    {
        return 'Your order is ready for pickup';
    }

    public function body(object $notifiable): string
    {
        return "Order {$this->order->order_number} is ready at {$this->pickupName($this->order)}. Pickup code: {$this->order->pickup_code}.";
    }

    public function url(object $notifiable): ?string
    {
        return $this->trackerUrl($this->order);
    }

    protected function subject(object $notifiable): string
    {
        return "Order {$this->order->order_number} is ready for pickup";
    }

    protected function mailLines(object $notifiable): array
    {
        return [
            "Order {$this->order->order_number} is ready at {$this->pickupName($this->order)}.",
            "Your pickup code is **{$this->order->pickup_code}**. Give it at the counter, or forward this email to whoever is collecting.",
            ...$this->pickupLines($this->order),
        ];
    }

    protected function actionLabel(): string
    {
        return 'View pickup details';
    }

    protected function extraData(object $notifiable): array
    {
        return $this->orderExtraData($this->order);
    }
}
