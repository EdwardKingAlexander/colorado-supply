<?php

namespace App\Notifications\Admin;

use App\Models\Order;
use App\Notifications\AppNotification;
use App\Notifications\Concerns\DescribesOrders;
use App\Notifications\NotificationType;

/** Staff alert: a will call order has sat at the counter for days. */
class PickupUncollectedAlert extends AppNotification
{
    use DescribesOrders;

    public function __construct(public Order $order, public int $days) {}

    public function type(): NotificationType
    {
        return NotificationType::AdminPickupUncollected;
    }

    public function title(object $notifiable): string
    {
        return "{$this->order->order_number} not picked up after {$this->days} days";
    }

    public function body(object $notifiable): string
    {
        $ready = $this->order->ready_for_pickup_at?->format('M j');

        return implode(' · ', array_filter([
            $this->buyerName($this->order),
            $this->pickupName($this->order),
            $ready ? "Ready since {$ready}" : null,
            filled($this->order->contact_phone) ? "Call {$this->order->contact_phone}" : null,
        ]));
    }

    public function url(object $notifiable): ?string
    {
        return $this->adminOrderUrl($this->order);
    }

    protected function actionLabel(): string
    {
        return 'Open order';
    }

    protected function extraData(object $notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->order_number, 'days' => $this->days];
    }
}
