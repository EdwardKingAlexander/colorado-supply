<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Stripe\ApiRequestor;
use Stripe\StripeClient;
use Tests\Support\Stripe\FakeStripeHttpClient;
use Tests\TestCase;

class StripeIntegrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    private function eventPayload(string $type, array $object, string $id = 'evt_test_123'): string
    {
        return json_encode([
            'id' => $id,
            'object' => 'event',
            'type' => $type,
            'data' => [
                'object' => $object,
            ],
        ]);
    }

    private function signatureHeader(string $payload): string
    {
        $secret = config('services.stripe.webhook_secret');
        $timestamp = time();

        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$signature}";
    }

    private function postWebhook(string $payload, string $signature)
    {
        return $this->call('POST', '/api/v1/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_full_checkout_to_paid_flow_without_hitting_stripe_api(): void
    {
        Notification::fake();

        $fakeHttpClient = new FakeStripeHttpClient([
            'post /v1/checkout/sessions' => [
                'body' => [
                    'id' => 'cs_test_flow',
                    'object' => 'checkout.session',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_test_flow',
                    'mode' => 'payment',
                    'payment_intent' => 'pi_test_flow',
                ],
            ],
        ]);

        ApiRequestor::setHttpClient($fakeHttpClient);
        $this->app->instance(StripeClient::class, new StripeClient('sk_test_dummy'));

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
            'grand_total' => 149.50,
        ]);

        $checkoutResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout");

        $checkoutResponse->assertSuccessful();
        $checkoutResponse->assertJson([
            'checkout_url' => 'https://checkout.stripe.com/c/pay/cs_test_flow',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_flow',
            'status' => PaymentStatus::Pending->value,
        ]);

        $payload = $this->eventPayload('payment_intent.succeeded', [
            'id' => 'pi_test_flow',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
        ], 'evt_test_flow');

        $webhookResponse = $this->postWebhook($payload, $this->signatureHeader($payload));

        $webhookResponse->assertSuccessful();

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway_session_id' => 'cs_test_flow',
            'gateway_payment_intent_id' => 'pi_test_flow',
            'status' => PaymentStatus::Paid->value,
        ]);
    }

    public function test_checkout_returns_clean_validation_error_when_stripe_api_call_fails(): void
    {
        $fakeHttpClient = new FakeStripeHttpClient([
            'post /v1/checkout/sessions' => [
                'body' => [
                    'error' => [
                        'type' => 'api_error',
                        'message' => 'Something went wrong on Stripe\'s end.',
                    ],
                ],
                'status' => 500,
            ],
        ]);

        ApiRequestor::setHttpClient($fakeHttpClient);
        $this->app->instance(StripeClient::class, new StripeClient('sk_test_dummy'));

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['order']);
        $response->assertJsonFragment([
            'order' => ['Unable to start checkout. Please try again shortly.'],
        ]);

        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
        ]);
    }
}
