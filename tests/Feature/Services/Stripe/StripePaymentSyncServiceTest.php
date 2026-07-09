<?php

namespace Tests\Feature\Services\Stripe;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderPaymentFailed;
use App\Notifications\OrderPaymentReceived;
use App\Services\Stripe\StripePaymentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Tests\TestCase;

class StripePaymentSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingPayment(Order $order, array $attributes = []): Payment
    {
        return $order->payments()->create(array_merge([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
        ], $attributes));
    }

    public function test_checkout_session_completed_sets_payment_intent_id(): void
    {
        $order = Order::factory()->create();
        $payment = $this->createPendingPayment($order);

        $session = Session::constructFrom([
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'payment_intent' => 'pi_test_123',
        ]);

        app(StripePaymentSyncService::class)->handleCheckoutSessionCompleted($session);

        $payment->refresh();

        $this->assertSame('pi_test_123', $payment->gateway_payment_intent_id);
    }

    public function test_payment_intent_succeeded_marks_paid_confirms_order_and_notifies(): void
    {
        Notification::fake();

        $order = Order::factory()->create();
        $payment = $this->createPendingPayment($order, ['gateway_payment_intent_id' => 'pi_test_123']);

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'latest_charge' => 'ch_test_123',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        app(StripePaymentSyncService::class)->handlePaymentIntentSucceeded($intent);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('ch_test_123', $payment->gateway_charge_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertTrue($order->isPaid());
        $this->assertSame(OrderStatus::Confirmed, $order->status);

        Notification::assertSentOnDemand(
            OrderPaymentReceived::class,
            fn (OrderPaymentReceived $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === $order->customer_email
                && $notification->order->is($order),
        );
    }

    public function test_payment_intent_succeeded_is_idempotent_and_does_not_renotify(): void
    {
        Notification::fake();

        $order = Order::factory()->create();
        $payment = $this->createPendingPayment($order, ['gateway_payment_intent_id' => 'pi_test_123']);

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $service = app(StripePaymentSyncService::class);

        $service->handlePaymentIntentSucceeded($intent);
        $service->handlePaymentIntentSucceeded($intent);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);

        Notification::assertSentOnDemandTimes(OrderPaymentReceived::class, 1);
    }

    public function test_payment_intent_failed_marks_failed_and_notifies(): void
    {
        Notification::fake();

        $order = Order::factory()->create();
        $payment = $this->createPendingPayment($order, ['gateway_payment_intent_id' => 'pi_test_123']);

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
            'last_payment_error' => [
                'code' => 'card_declined',
                'message' => 'Your card was declined.',
            ],
        ]);

        app(StripePaymentSyncService::class)->handlePaymentIntentFailed($intent);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame('card_declined', $payment->failure_code);
        $this->assertSame(PaymentStatus::Failed, $order->payment_status);

        Notification::assertSentOnDemand(
            OrderPaymentFailed::class,
            fn (OrderPaymentFailed $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === $order->customer_email
                && $notification->failureMessage === 'Your card was declined.',
        );
    }

    public function test_charge_refunded_marks_payment_and_order_refunded(): void
    {
        $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);

        $payment = $this->createPendingPayment($order, [
            'status' => PaymentStatus::Paid,
            'gateway_payment_intent_id' => 'pi_test_123',
            'gateway_charge_id' => 'ch_test_123',
            'paid_at' => now(),
        ]);

        $charge = Charge::constructFrom([
            'id' => 'ch_test_123',
            'object' => 'charge',
            'refunds' => [
                'object' => 'list',
                'data' => [
                    ['id' => 're_test_123', 'object' => 'refund'],
                ],
            ],
        ]);

        app(StripePaymentSyncService::class)->handleChargeRefunded($charge);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        $this->assertSame('re_test_123', $payment->gateway_refund_id);
        $this->assertNotNull($payment->refunded_at);
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
    }
}
