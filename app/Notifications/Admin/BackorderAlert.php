<?php

namespace App\Notifications\Admin;

use App\Models\Order;
use App\Notifications\AppNotification;
use App\Notifications\Concerns\DescribesOrders;
use App\Notifications\NotificationType;

/** Staff alert: an order asked for more than is in stock. */
class BackorderAlert extends AppNotification
{
    use DescribesOrders;

    /**
     * @param  list<array{name: string, ordered: int, on_hand: int}>  $lines
     */
    public function __construct(public Order $order, public array $lines) {}

    public function type(): NotificationType
    {
        return NotificationType::AdminBackorderCreated;
    }

    public function title(object $notifiable): string
    {
        return "Backorder on {$this->order->order_number}";
    }

    public function body(object $notifiable): string
    {
        return collect($this->lines)
            ->map(fn (array $line) => "{$line['name']}: ordered {$line['ordered']}, on hand {$line['on_hand']}")
            ->implode('; ').'.';
    }

    public function url(object $notifiable): ?string
    {
        return $this->adminOrderUrl($this->order);
    }

    protected function mailLines(object $notifiable): array
    {
        return [
            "{$this->buyerName($this->order)} ordered more than is in stock:",
            ...array_map(fn (array $line) => "- {$line['name']}: ordered {$line['ordered']}, on hand {$line['on_hand']}", $this->lines),
        ];
    }

    protected function actionLabel(): string
    {
        return 'Open order';
    }

    protected function extraData(object $notifiable): array
    {
        return ['order_id' => $this->order->id, 'order_number' => $this->order->order_number, 'lines' => $this->lines];
    }
}
