<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\ProcessStripeWebhookEvent;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

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

    private function signatureHeader(string $payload, ?string $secret = null, ?int $timestamp = null): string
    {
        $secret ??= config('services.stripe.webhook_secret');
        $timestamp ??= time();

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

    public function test_valid_signature_creates_stripe_event_and_dispatches_job(): void
    {
        Bus::fake();

        $payload = $this->eventPayload('checkout.session.completed', [
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'payment_intent' => 'pi_test_123',
        ]);

        $response = $this->postWebhook($payload, $this->signatureHeader($payload));

        $response->assertSuccessful();

        $this->assertDatabaseHas('stripe_events', [
            'stripe_event_id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
        ]);

        $stripeEvent = StripeEvent::where('stripe_event_id', 'evt_test_123')->first();

        Bus::assertDispatched(
            ProcessStripeWebhookEvent::class,
            fn (ProcessStripeWebhookEvent $job) => $job->stripeEventId === $stripeEvent->id,
        );
    }

    public function test_invalid_signature_is_rejected(): void
    {
        Bus::fake();

        $payload = $this->eventPayload('checkout.session.completed', [
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
        ]);

        $response = $this->postWebhook($payload, $this->signatureHeader($payload, 'whsec_wrong_secret'));

        $response->assertStatus(400);

        $this->assertDatabaseMissing('stripe_events', [
            'stripe_event_id' => 'evt_test_123',
        ]);

        Bus::assertNotDispatched(ProcessStripeWebhookEvent::class);
    }

    public function test_duplicate_event_id_is_not_reprocessed(): void
    {
        Bus::fake();

        $payload = $this->eventPayload('checkout.session.completed', [
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
        ]);

        $this->postWebhook($payload, $this->signatureHeader($payload))->assertSuccessful();

        $response = $this->postWebhook($payload, $this->signatureHeader($payload));

        $response->assertSuccessful();

        $this->assertDatabaseCount('stripe_events', 1);

        Bus::assertDispatchedTimes(ProcessStripeWebhookEvent::class, 1);
    }

    public function test_payment_intent_succeeded_webhook_marks_order_paid_and_confirmed_end_to_end(): void
    {
        Notification::fake();

        $order = Order::factory()->create();

        $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'gateway_payment_intent_id' => 'pi_test_123',
        ]);

        $payload = $this->eventPayload('payment_intent.succeeded', [
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
        ], 'evt_test_paid');

        $response = $this->postWebhook($payload, $this->signatureHeader($payload));

        $response->assertSuccessful();

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway_payment_intent_id' => 'pi_test_123',
            'status' => PaymentStatus::Paid->value,
        ]);

        $stripeEvent = StripeEvent::where('stripe_event_id', 'evt_test_paid')->first();
        $this->assertNotNull($stripeEvent->processed_at);
    }
}
