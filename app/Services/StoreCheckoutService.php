<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class StoreCheckoutService
{
    public function __construct(private OrderNumberGenerator $orderNumberGenerator) {}

    public function createFromCart(User|Admin $user, array $validated): Order
    {
        $billingAddress = $validated['billing_address'];
        $shippingSameAsBilling = $validated['shipping_same_as_billing'] ?? true;
        $shippingAddress = $shippingSameAsBilling || empty($validated['shipping_address'])
            ? $billingAddress
            : $validated['shipping_address'];

        $subtotal = array_reduce($validated['items'], function (float $carry, array $item) {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0.0);

        $order = DB::transaction(function () use ($user, $validated, $billingAddress, $shippingAddress, $subtotal) {
            $order = Order::create([
                'order_number' => $this->orderNumberGenerator->next(),
                'portal_user_id' => $user instanceof User ? $user->id : null,
                'company_id' => $user instanceof User ? $user->company_id : null,
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'po_number' => $validated['po_number'] ?? null,
                'job_number' => $validated['job_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'billing_address' => $billingAddress,
                'shipping_address' => $shippingAddress,
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'shipping_total' => 0,
                'discount_total' => 0,
                'grand_total' => $subtotal,
                'tax_rate' => 0,
                'status' => OrderStatus::Draft,
                'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'location_id' => $item['location_id'] ?? null,
                    'sku' => $item['slug'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'line_discount' => 0,
                    'line_total' => $item['price'] * $item['quantity'],
                ]);
            }

            return $order;
        });

        if ($order->customer_email) {
            Mail::to($order->customer_email)
                ->send(new OrderConfirmationMail($order->load('items')));
        }

        return $order;
    }
}
