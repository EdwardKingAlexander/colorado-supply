<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class OrderPaymentReceived extends AppNotification
{
    public function __construct(public Order $order) {}

    public function type(): NotificationType
    {
        return NotificationType::PaymentReceived;
    }

    public function title(object $notifiable): string
    {
        return "Payment received for order {$this->order->order_number}";
    }

    public function body(object $notifiable): string
    {
        return 'Order total: $'.number_format((float) $this->order->grand_total, 2).'. Your order is confirmed and will be processed shortly.';
    }

    public function url(object $notifiable): ?string
    {
        return $this->trackerUrl();
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
            ->action('Track Your Order', $this->trackerUrl())
            ->line('You can check your order status anytime using the button above — no sign-in required.');
    }

    protected function extraData(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'transition' => 'payment_received',
            'tracker_url' => $this->trackerUrl(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function trackerUrl(): string
    {
        return URL::signedRoute('orders.track', ['order' => $this->order]);
    }
}
