<?php

namespace App\Services\Paypal;

use App\Models\Payment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaypalCaptureService
{
    public function __construct(private PaypalClientFactory $client) {}

    /**
     * Capture a previously approved PayPal order.
     *
     * @return string The PayPal capture id.
     *
     * @throws RuntimeException if PayPal rejects the request or the capture did not complete.
     */
    public function captureOrder(Payment $payment): string
    {
        try {
            $response = Http::withToken($this->client->accessToken())
                ->post("{$this->client->baseUrl()}/v2/checkout/orders/{$payment->gateway_session_id}/capture")
                ->throw();
        } catch (RequestException $e) {
            Log::error('PayPal order capture failed', [
                'payment_id' => $payment->id,
                'paypal_order_id' => $payment->gateway_session_id,
                'response' => $e->response?->json(),
            ]);

            throw new RuntimeException('Unable to complete PayPal payment. Please try again shortly.', previous: $e);
        }

        if ($response->json('status') !== 'COMPLETED') {
            throw new RuntimeException('Unable to complete PayPal payment. Please try again shortly.');
        }

        $captureId = $response->json('purchase_units.0.payments.captures.0.id');

        if (! $captureId) {
            throw new RuntimeException('Unable to complete PayPal payment. Please try again shortly.');
        }

        return $captureId;
    }
}
