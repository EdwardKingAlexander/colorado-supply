<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\DescribesOrders;

class PickedUpNotification extends AppNotification
{
    use DescribesOrders;

    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return NotificationType::PickedUp;
    }

    public function title(object $notifiable): string
    {
        return "Order {$this->order->order_number} was picked up";
    }

    public function body(object $notifiable): string
    {
        $who = filled($this->order->picked_up_by) ? " by {$this->order->picked_up_by}" : '';
        $when = $this->order->picked_up_at?->format('F j, Y \a\t g:i A');

        return "Collected{$who}".($when ? " on {$when}" : '').'. Thank you for your business.';
    }

    public function url(object $notifiable): ?string
    {
        return $this->trackerUrl($this->order);
    }

    protected function mailLines(object $notifiable): array
    {
        return [
            $this->body($notifiable),
            "If you weren't expecting this, reply to this email right away.",
        ];
    }

    protected function actionLabel(): string
    {
        return 'View order';
    }

    protected function extraData(object $notifiable): array
    {
        return $this->orderExtraData($this->order);
    }
}
