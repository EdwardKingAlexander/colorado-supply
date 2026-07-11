<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;

class OrderNotifier
{
    /**
     * Deliver an order notification to the buyer: account holders get their
     * configured channels (mail + database bell), guest orders fall back to
     * a routed email when the order carries one.
     */
    public static function send(Order $order, BaseNotification $notification): void
    {
        if ($order->portalUser) {
            $order->portalUser->notify($notification);

            return;
        }

        $email = $order->customer_email;

        if ($email) {
            Notification::route('mail', $email)->notify($notification);
        }
    }
}
