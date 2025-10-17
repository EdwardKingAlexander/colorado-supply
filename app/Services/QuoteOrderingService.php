<?php

namespace App\Services;

use App\Events\QuoteConvertedToOrder;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class QuoteOrderingService
{
    protected array $allowedPaymentMethods = [
        'credit_card',
        'debit_card',
        'online_portal',
    ];

    /**
     * Convert a quote to an order
     *
     * @throws ValidationException
     */
    public function convert(Quote $quote, array $input): Order
    {
        // Validate payment method
        $paymentMethod = $input['payment_method'] ?? null;

        if (!in_array($paymentMethod, $this->allowedPaymentMethods, true)) {
            throw ValidationException::withMessages([
                'payment_method' => [
                    'Invalid payment method. Allowed methods: ' . implode(', ', $this->allowedPaymentMethods)
                ],
            ]);
        }

        return DB::transaction(function () use ($quote, $input, $paymentMethod) {
            // Create the order
            $order = Order::create([
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'payment_method' => $paymentMethod,
                'po_number' => $input['po_number'] ?? null,
                'job_number' => $input['job_number'] ?? null,
                'order_total' => $quote->grand_total,
                'status' => 'created',
                'notes' => $input['notes'] ?? null,
                // Copy walk-in details if applicable
                'walk_in_label' => $quote->walk_in_label,
                'walk_in_org' => $quote->walk_in_org,
                'walk_in_contact_name' => $quote->walk_in_contact_name,
                'walk_in_email' => $quote->walk_in_email,
                'walk_in_phone' => $quote->walk_in_phone,
                'walk_in_billing_json' => $quote->walk_in_billing_json,
                'walk_in_shipping_json' => $quote->walk_in_shipping_json,
            ]);

            // Update quote status
            $quote->status = 'ordered';
            $quote->save();

            // Log the conversion
            Log::info('Quote converted to order', [
                'quote_id' => $quote->id,
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'payment_method' => $paymentMethod,
            ]);

            // Emit event
            event(new QuoteConvertedToOrder($quote, $order));

            return $order;
        });
    }
}
