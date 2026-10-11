<?php

namespace App\Notifications\Admin;

use App\Models\Order;
use App\Notifications\AppNotification;
use App\Notifications\Concerns\DescribesOrders;
use App\Notifications\NotificationType;

/** Staff alert for a card or PayPal payment that succeeded or failed. */
class PaymentAlert extends AppNotification
{
    use DescribesOrders;

    public function __construct(
        public Order $order,
        public bool $succeeded,
        public ?string $reason = null,
    ) {}

    public function type(): NotificationType
    {
        return $this->succeeded ? NotificationType::AdminPaymentReceived : NotificationType::AdminPaymentFailed;
    }

    public function title(object $notifiable): string
    {
        return $this->succeeded
            ? "Payment received for {$this->order->order_number}"
            : "Payment failed for {$this->order->order_number}";
    }

    public function body(object $notifiable): string
    {
        $base = "{$this->buyerName($this->order)} · {$this->money($this->order->grand_total)}";

        return $this->succeeded || blank($this->reason)
            ? $base
            : "{$base} · {$this->reason}";
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
        return ['order_id' => $this->order->id, 'order_number' => $this->order->order_number];
    }
}
