<?php

namespace App\Jobs;

use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {
    }

    public function handle(): void
    {
        // Send to customer
        if ($this->order->customer_email) {
            Mail::to($this->order->customer_email)
                ->send(new OrderConfirmedMail($this->order));
        }

        // Always send copy to edward@cogovsupply.com
        Mail::to('edward@cogovsupply.com')
            ->send(new OrderConfirmedMail($this->order));
    }
}
