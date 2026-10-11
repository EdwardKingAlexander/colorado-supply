<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class OrderPaymentFailed extends AppNotification
{
    public function __construct(
        public Order $order,
        public ?string $failureMessage = null,
    ) {}

    public function type(): NotificationType
    {
        return NotificationType::PaymentFailed;
    }

    public function title(object $notifiable): string
    {
        return "Payment failed for order {$this->order->order_number}";
    }

    public function body(object $notifiable): string
    {
        $reason = $this->failureMessage ? 'Reason: '.rtrim($this->failureMessage, '.').'. ' : '';

        return $reason.'Please try again or use a different payment method.';
    }

    public function url(object $notifiable): ?string
    {
        return $this->trackerUrl();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Payment Failed for Order {$this->order->order_number}")
            ->error()
            ->greeting('Payment Unsuccessful')
            ->line("We weren't able to process your payment for order {$this->order->order_number}.");

        if ($this->failureMessage) {
            $message->line("Reason: {$this->failureMessage}");
        }

        return $message
            ->line('Please try again or use a different payment method.')
            ->action('Track Your Order', $this->trackerUrl());
    }

    protected function extraData(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'failure_message' => $this->failureMessage,
            'transition' => 'payment_failed',
            'tracker_url' => $this->trackerUrl(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function trackerUrl(): string
    {
        return URL::signedRoute('orders.track', ['order' => $this->order]);
    }
}
