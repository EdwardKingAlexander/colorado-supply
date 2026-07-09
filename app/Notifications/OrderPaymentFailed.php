<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?string $failureMessage = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
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
            ->action('Retry Payment', route('store.checkout.cancel', ['order' => $this->order->id]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'failure_message' => $this->failureMessage,
        ];
    }
}
