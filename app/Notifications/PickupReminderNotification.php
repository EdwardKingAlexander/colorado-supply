<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\DescribesOrders;

class PickupReminderNotification extends AppNotification
{
    use DescribesOrders;

    public function __construct(public Order $order, public int $days) {}

    public function type(): NotificationType
    {
        return NotificationType::PickupReminder;
    }

    public function title(object $notifiable): string
    {
        return 'Your order is waiting for pickup';
    }

    public function body(object $notifiable): string
    {
        return "Order {$this->order->order_number} has been ready at {$this->pickupName($this->order)} for {$this->days} days. Pickup code: {$this->order->pickup_code}.";
    }

    public function url(object $notifiable): ?string
    {
        return $this->trackerUrl($this->order);
    }

    protected function subject(object $notifiable): string
    {
        return "Reminder: order {$this->order->order_number} is waiting for pickup";
    }

    protected function mailLines(object $notifiable): array
    {
        return [
            $this->body($notifiable),
            ...$this->pickupLines($this->order),
            "If you can't come in, reply to this email and we'll help.",
        ];
    }

    protected function smsText(object $notifiable): string
    {
        return "Reminder: order {$this->order->order_number} is waiting at {$this->pickupName($this->order)}. Pickup code {$this->order->pickup_code}.";
    }

    protected function actionLabel(): string
    {
        return 'View pickup details';
    }

    protected function extraData(object $notifiable): array
    {
        return $this->orderExtraData($this->order) + ['days' => $this->days];
    }
}
