<?php

namespace Tests\Support\Paypal;

use Illuminate\Support\Facades\Http;

/**
 * Canned Http::fake() responses for PayPal's REST API, for use in feature tests.
 */
trait FakesPaypalHttp
{
    /**
     * @param  array<string, mixed>  $overrides  Replace or add fake responses, keyed by URL pattern.
     */
    protected function fakePaypal(array $overrides = []): void
    {
        Http::fake(array_merge([
            '*/v1/oauth2/token' => Http::response([
                'access_token' => 'A21AAtest_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400,
            ], 200),
            '*/v2/checkout/orders/*/capture' => Http::response([
                'id' => 'PAYPALORDER123',
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [
                        'captures' => [[
                            'id' => 'CAPTURE123',
                        ]],
                    ],
                ]],
            ], 200),
            '*/v2/checkout/orders' => Http::response([
                'id' => 'PAYPALORDER123',
                'status' => 'CREATED',
                'links' => [
                    ['href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPALORDER123', 'rel' => 'approve', 'method' => 'GET'],
                ],
            ], 201),
            '*/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ], 200),
        ], $overrides));
    }
}
