<?php

namespace Tests\Feature\Jobs;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\ProcessStripeWebhookEvent;
use App\Models\Company;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessStripeWebhookEventTest extends TestCase
{
    use RefreshDatabase;

    private function makeStripeEvent(string $type, array $object, string $id = 'evt_test_123'): StripeEvent
    {
        return StripeEvent::create([
            'stripe_event_id' => $id,
            'type' => $type,
            'payload' => [
                'id' => $id,
                'object' => 'event',
                'type' => $type,
                'data' => ['object' => $object],
            ],
        ]);
    }

    private function runJob(StripeEvent $stripeEvent): void
    {
        $job = new ProcessStripeWebhookEvent($stripeEvent->id);

        app()->call([$job, 'handle']);
    }

    public function test_checkout_session_completed_links_payment_intent_to_payment(): void
    {
        $order = Order::factory()->create();

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
        ]);

        $stripeEvent = $this->makeStripeEvent('checkout.session.completed', [
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'payment_intent' => 'pi_test_123',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $this->runJob($stripeEvent);

        $payment->refresh();
        $stripeEvent->refresh();

        $this->assertSame('pi_test_123', $payment->gateway_payment_intent_id);
        $this->assertNotNull($stripeEvent->processed_at);
    }

    public function test_payment_intent_succeeded_marks_payment_and_order_paid(): void
    {
        $order = Order::factory()->create();

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'gateway_payment_intent_id' => 'pi_test_123',
        ]);

        $stripeEvent = $this->makeStripeEvent('payment_intent.succeeded', [
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'latest_charge' => 'ch_test_123',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $this->runJob($stripeEvent);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('ch_test_123', $payment->gateway_charge_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertTrue($order->isPaid());
    }

    public function test_payment_intent_succeeded_resolves_payment_via_order_metadata_when_no_payment_intent_link(): void
    {
        $order = Order::factory()->create();

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
        ]);

        $stripeEvent = $this->makeStripeEvent('payment_intent.succeeded', [
            'id' => 'pi_test_456',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $this->runJob($stripeEvent);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('pi_test_456', $payment->gateway_payment_intent_id);
        $this->assertTrue($order->isPaid());
    }

    public function test_payment_intent_failed_marks_payment_and_order_failed(): void
    {
        $order = Order::factory()->create();

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'gateway_payment_intent_id' => 'pi_test_123',
        ]);

        $stripeEvent = $this->makeStripeEvent('payment_intent.payment_failed', [
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
            'last_payment_error' => [
                'code' => 'card_declined',
                'message' => 'Your card was declined.',
            ],
        ]);

        $this->runJob($stripeEvent);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame('card_declined', $payment->failure_code);
        $this->assertSame('Your card was declined.', $payment->failure_message);
        $this->assertSame(PaymentStatus::Failed, $order->payment_status);
    }

    public function test_charge_refunded_marks_payment_and_order_refunded(): void
    {
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
        ]);

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Paid,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'gateway_payment_intent_id' => 'pi_test_123',
            'gateway_charge_id' => 'ch_test_123',
            'paid_at' => now(),
        ]);

        $stripeEvent = $this->makeStripeEvent('charge.refunded', [
            'id' => 'ch_test_123',
            'object' => 'charge',
            'refunds' => [
                'object' => 'list',
                'data' => [
                    ['id' => 're_test_123', 'object' => 'refund'],
                ],
            ],
        ]);

        $this->runJob($stripeEvent);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        $this->assertSame('re_test_123', $payment->gateway_refund_id);
        $this->assertNotNull($payment->refunded_at);
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
    }

    public function test_unhandled_event_type_is_marked_processed_without_error(): void
    {
        $stripeEvent = $this->makeStripeEvent('invoice.paid', [
            'id' => 'in_test_123',
            'object' => 'invoice',
        ]);

        $this->runJob($stripeEvent);

        $stripeEvent->refresh();

        $this->assertNotNull($stripeEvent->processed_at);
    }

    public function test_already_processed_event_is_not_reprocessed(): void
    {
        $order = Order::factory()->create();

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'gateway_payment_intent_id' => 'pi_test_123',
        ]);

        $stripeEvent = $this->makeStripeEvent('payment_intent.succeeded', [
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $stripeEvent->markAsProcessed();

        $this->runJob($stripeEvent);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Pending, $payment->status);
    }

    public function test_order_resolution_bypasses_company_scope_for_authenticated_user_in_another_company(): void
    {
        $orderCompany = Company::create(['name' => 'Order Co', 'slug' => 'order-co']);
        $userCompany = Company::create(['name' => 'User Co', 'slug' => 'user-co']);

        $order = Order::factory()->create(['company_id' => $orderCompany->id]);

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'gateway_payment_intent_id' => 'pi_test_123',
        ]);

        $user = User::factory()->create(['company_id' => $userCompany->id]);
        $this->actingAs($user);

        $stripeEvent = $this->makeStripeEvent('payment_intent.succeeded', [
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $this->runJob($stripeEvent);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertTrue($order->isPaid());
    }
}
