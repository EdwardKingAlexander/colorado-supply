<?php

namespace App\Services\Paypal;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaypalCheckoutService
{
    public function __construct(private PaypalClientFactory $client) {}

    /**
     * Create a PayPal Order (v2 Checkout Orders, Standard Checkout flow) for
     * the given order and record a pending Payment row tracking it.
     *
     * @return array{approve_url: string, paypal_order_id: string}
     *
     * @throws RuntimeException if the order cannot be paid or PayPal rejects the request.
     */
    public function createOrderForOrder(Order $order): array
    {
        if (! $order->canBePaid()) {
            throw new RuntimeException("Order {$order->order_number} cannot be paid.");
        }

        try {
            $response = Http::withToken($this->client->accessToken())
                ->post("{$this->client->baseUrl()}/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => (string) $order->id,
                        'custom_id' => (string) $order->id,
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format((float) $order->grand_total, 2, '.', ''),
                        ],
                    ]],
                    'application_context' => [
                        'return_url' => route('store.checkout.paypal.return', ['order' => $order->id]),
                        'cancel_url' => route('store.checkout.cancel', ['order' => $order->id]),
                        'user_action' => 'PAY_NOW',
                        'shipping_preference' => 'NO_SHIPPING',
                    ],
                ])
                ->throw();
        } catch (RequestException $e) {
            Log::error('PayPal checkout order creation failed', [
                'order_id' => $order->id,
                'response' => $e->response?->json(),
            ]);

            throw new RuntimeException('Unable to start PayPal checkout. Please try again shortly.', previous: $e);
        }

        $paypalOrderId = $response->json('id');
        $approveUrl = $this->extractApproveUrl($response->json('links', []));

        $this->upsertPendingPayment($order, $paypalOrderId);

        return [
            'approve_url' => $approveUrl,
            'paypal_order_id' => $paypalOrderId,
        ];
    }

    /**
     * @param  array<int, array{rel?: string, href?: string}>  $links
     */
    private function extractApproveUrl(array $links): string
    {
        foreach ($links as $link) {
            if (($link['rel'] ?? null) === 'approve') {
                return $link['href'];
            }
        }

        throw new RuntimeException('Unable to start PayPal checkout. Please try again shortly.');
    }

    private function upsertPendingPayment(Order $order, string $paypalOrderId): void
    {
        $attributes = [
            'method' => PaymentMethod::Paypal,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'paypal',
            'gateway_session_id' => $paypalOrderId,
        ];

        $payment = $order->payments()
            ->where('gateway', 'paypal')
            ->where('status', PaymentStatus::Pending)
            ->first();

        if ($payment) {
            $payment->update($attributes);

            return;
        }

        $order->payments()->create($attributes);
    }
}
