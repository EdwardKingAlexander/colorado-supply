<?php

namespace App\Services;

use App\Events\QuoteConvertedToOrder;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuoteOrderingService
{
    /**
     * Convert a quote to an order
     *
     * @throws ValidationException
     */
    public function convert(Quote $quote, array $input): Order
    {
        $quote->loadMissing(['items', 'customer']);

        $sendEmail = (bool) ($input['send_email'] ?? false);

        $order = DB::transaction(function () use ($quote, $input) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'portal_user_id' => $quote->portal_user_id,
                'payment_method' => $input['payment_method'] ?? null,
                'po_number' => $input['po_number'] ?? null,
                'job_number' => $input['job_number'] ?? null,
                'notes' => $input['notes'] ?? null,
                'subtotal' => $quote->subtotal,
                'tax_total' => $quote->tax_total,
                'shipping_total' => 0,
                'discount_total' => $quote->discount_amount,
                'grand_total' => $quote->grand_total,
                'tax_rate' => $quote->tax_rate,
                'status' => $quote->grand_total > 0 ? 'draft' : 'confirmed',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'contact_name' => $quote->walk_in_contact_name,
                'contact_email' => $quote->walk_in_email,
                'contact_phone' => $quote->walk_in_phone,
                'company_name' => $quote->walk_in_org,
                'cash_card_name' => $quote->walk_in_contact_name,
                'cash_card_email' => $quote->walk_in_email,
                'cash_card_phone' => $quote->walk_in_phone,
                'cash_card_company' => $quote->walk_in_org,
                'billing_address' => $quote->walk_in_billing_json,
                'shipping_address' => $quote->walk_in_shipping_json,
            ]);

            foreach ($quote->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'description' => $item->notes,
                    'quantity' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'line_discount' => 0,
                    'line_total' => $item->line_total ?: $item->line_subtotal,
                    'meta' => [
                        'quote_item_id' => $item->id,
                    ],
                ]);
            }

            $quote->update([
                'status' => 'ordered',
            ]);

            event(new QuoteConvertedToOrder($quote, $order));

            return $order;
        });

        if ($sendEmail && $order->customer_email) {
            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order->refresh()->load('items')));
        }

        return $order;
    }

    protected function generateOrderNumber(): string
    {
        return sprintf('ORD-%s-%s', now()->format('Ymd'), Str::upper(Str::random(4)));
    }
}
