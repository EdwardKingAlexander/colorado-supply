<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Quote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteConvertedToOrder
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public Order $order
    ) {
    }
}
