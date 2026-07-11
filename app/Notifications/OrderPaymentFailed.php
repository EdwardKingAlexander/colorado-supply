<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrderPaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?string $failureMessage = null,
    ) {}

    public function via(object $notifiable): array
    {
        // Account holders also get the in-app bell; guests are mail-only.
        return $notifiable instanceof User ? ['mail', 'database'] : ['mail'];
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

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'failure_message' => $this->failureMessage,
            'transition' => 'payment_failed',
            'label' => "Payment failed for order {$this->order->order_number}",
            'tracker_url' => $this->trackerUrl(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function trackerUrl(): string
    {
        return URL::signedRoute('orders.track', ['order' => $this->order]);
    }
}
