<?php

namespace App\Notifications\Admin;

use App\Models\Order;
use App\Notifications\AppNotification;
use App\Notifications\Concerns\DescribesOrders;
use App\Notifications\NotificationType;

/** Staff alert for a new store order; will call orders get their own type. */
class NewOrderAlert extends AppNotification
{
    use DescribesOrders;

    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return $this->order->isWillCall() ? NotificationType::AdminNewWillCallOrder : NotificationType::AdminNewOrder;
    }

    public function title(object $notifiable): string
    {
        return $this->order->isWillCall()
            ? "New will call order {$this->order->order_number}"
            : "New order {$this->order->order_number}";
    }

    public function body(object $notifiable): string
    {
        $this->order->loadMissing('items', 'pickupLocation');
        $count = $this->order->items->count();

        return implode(' · ', array_filter([
            $this->buyerName($this->order),
            $count.' '.($count === 1 ? 'item' : 'items'),
            $this->money($this->order->grand_total),
            $this->order->isWillCall() ? 'Pickup at '.$this->pickupName($this->order) : $this->shipsTo(),
            'Payment: '.$this->order->payment_status->label(),
        ]));
    }

    public function url(object $notifiable): ?string
    {
        return $this->adminOrderUrl($this->order);
    }

    protected function smsText(object $notifiable): string
    {
        $this->order->loadMissing('items');
        $count = $this->order->items->count();

        return "{$this->title($notifiable)} from {$this->buyerName($this->order)}, {$count} ".($count === 1 ? 'item' : 'items').'.';
    }

    protected function actionLabel(): string
    {
        return 'Open order';
    }

    protected function extraData(object $notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->order_number];
    }

    private function shipsTo(): ?string
    {
        $address = $this->order->shipping_address ?? [];
        $place = trim(implode(', ', array_filter([$address['city'] ?? null, $address['state'] ?? null])));

        return $place !== '' ? "Ships to {$place}" : null;
    }
}
