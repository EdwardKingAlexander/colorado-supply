<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrderPaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        // Account holders also get the in-app bell; guests are mail-only.
        return $notifiable instanceof User ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Received for Order {$this->order->order_number}")
            ->greeting('Thank you for your payment!')
            ->line("We've received your payment for order {$this->order->order_number}.")
            ->line('Order total: $'.number_format((float) $this->order->grand_total, 2))
            ->line('Your order is now confirmed and will be processed shortly.')
            // Signed tracker URL: works without login or email verification,
            // unlike the auth-gated checkout success page it used to link to.
            ->action('Track Your Order', URL::signedRoute('orders.track', ['order' => $this->order]))
            ->line('You can check your order status anytime using the button above — no sign-in required.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'transition' => 'payment_received',
            'label' => "Payment received for order {$this->order->order_number}",
            'tracker_url' => URL::signedRoute('orders.track', ['order' => $this->order]),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
