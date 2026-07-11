<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Single parameterized notification for buyer-facing order status
 * transitions (shipped / delivered / fulfilled / cancelled / refunded).
 * Payment received/failed keep their dedicated notifications.
 */
class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $transition  One of: shipped, delivered, fulfilled, cancelled, refunded
     * @param  array{carrier?: ?string, tracking_number?: ?string}  $context
     */
    public function __construct(
        public Order $order,
        public string $transition,
        public array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        // The bell (database channel) only exists for account holders; guest
        // orders are notified by email alone via Notification::route().
        return $notifiable instanceof User ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subjectLine())
            ->greeting($this->headline());

        foreach ($this->bodyLines() as $line) {
            $mail->line($line);
        }

        return $mail
            ->action('Track Your Order', $this->trackerUrl())
            ->line('You can check your order status anytime using the button above — no sign-in required.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'transition' => $this->transition,
            'label' => $this->headline(),
            'tracker_url' => $this->trackerUrl(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function trackerUrl(): string
    {
        return URL::signedRoute('orders.track', ['order' => $this->order]);
    }

    protected function subjectLine(): string
    {
        return match ($this->transition) {
            'shipped' => "Your order {$this->order->order_number} has shipped",
            'delivered' => "Your order {$this->order->order_number} has been delivered",
            'fulfilled' => "Your order {$this->order->order_number} is complete",
            'cancelled' => "Your order {$this->order->order_number} has been cancelled",
            'refunded' => "Your order {$this->order->order_number} has been refunded",
            default => "Update on your order {$this->order->order_number}",
        };
    }

    protected function headline(): string
    {
        return match ($this->transition) {
            'shipped' => 'Your order has shipped',
            'delivered' => 'Your order has been delivered',
            'fulfilled' => 'Your order is complete',
            'cancelled' => 'Your order has been cancelled',
            'refunded' => 'Your order has been refunded',
            default => 'Your order has been updated',
        };
    }

    /**
     * @return list<string>
     */
    protected function bodyLines(): array
    {
        return match ($this->transition) {
            'shipped' => array_values(array_filter([
                "Order {$this->order->order_number} is on its way.",
                filled($this->context['carrier'] ?? null) ? 'Carrier: '.$this->context['carrier'] : null,
                filled($this->context['tracking_number'] ?? null) ? 'Tracking number: '.$this->context['tracking_number'] : null,
            ])),
            'delivered' => ["Order {$this->order->order_number} has been delivered. We hope everything arrived as expected."],
            'fulfilled' => ["All items in order {$this->order->order_number} have been fulfilled. Thank you for your business."],
            'cancelled' => ["Order {$this->order->order_number} has been cancelled. If this is unexpected, reply to this email and our team will assist."],
            'refunded' => ["Your payment for order {$this->order->order_number} has been refunded. Depending on your bank, the funds may take a few business days to appear."],
            default => ["Order {$this->order->order_number} has a status update."],
        };
    }
}
