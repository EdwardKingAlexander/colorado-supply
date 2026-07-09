<?php

namespace App\Services\Stripe;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function __construct(private StripeClient $stripe) {}

    /**
     * Create a Stripe Checkout Session for the given order and record a
     * pending Payment row tracking the session.
     *
     * @throws RuntimeException if the order cannot be paid or Stripe rejects the request.
     */
    public function createSessionForOrder(Order $order): Session
    {
        if (! $order->canBePaid()) {
            throw new RuntimeException("Order {$order->order_number} cannot be paid.");
        }

        try {
            $session = $this->stripe->checkout->sessions->create([
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $order->getGrandTotalInCents(),
                        'product_data' => [
                            'name' => "Order {$order->order_number}",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                ],
                'success_url' => route('store.checkout.success', ['order' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('store.checkout.cancel', ['order' => $order->id]),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout session creation failed', [
                'order_id' => $order->id,
                'stripe_error_code' => $e->getStripeCode(),
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to start checkout. Please try again shortly.', previous: $e);
        }

        $this->upsertPendingPayment($order, $session);

        return $session;
    }

    private function upsertPendingPayment(Order $order, Session $session): void
    {
        $attributes = [
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => $session->id,
        ];

        $payment = $order->payments()
            ->where('gateway', 'stripe')
            ->where('status', PaymentStatus::Pending)
            ->first();

        if ($payment) {
            $payment->update($attributes);

            return;
        }

        $order->payments()->create($attributes);
    }
}
