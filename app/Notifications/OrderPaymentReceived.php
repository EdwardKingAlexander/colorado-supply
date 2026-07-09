<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Received for Order {$this->order->order_number}")
            ->greeting('Thank you for your payment!')
            ->line("We've received your payment for order {$this->order->order_number}.")
            ->line('Order total: $'.number_format((float) $this->order->grand_total, 2))
            ->line('Your order is now confirmed and will be processed shortly.')
            ->action('View Order', route('store.checkout.success', ['order' => $this->order->id]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
