<?php

namespace Tests\Feature\Services\Paypal;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderPaymentReceived;
use App\Services\Paypal\PaypalPaymentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaypalPaymentSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingPayment(Order $order, array $attributes = []): Payment
    {
        return $order->payments()->create(array_merge([
            'method' => PaymentMethod::Paypal,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'paypal',
            'gateway_session_id' => 'PAYPALORDER123',
        ], $attributes));
    }

    public function test_capture_completed_marks_paid_confirms_order_and_notifies(): void
    {
        Notification::fake();

        $order = Order::factory()->create();
        $payment = $this->createPendingPayment($order);

        $resource = [
            'id' => 'CAPTURE123',
            'supplementary_data' => [
                'related_ids' => [
                    'order_id' => 'PAYPALORDER123',
                ],
            ],
            'custom_id' => (string) $order->id,
        ];

        app(PaypalPaymentSyncService::class)->handleCaptureCompleted($resource);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('CAPTURE123', $payment->gateway_charge_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertTrue($order->isPaid());
        $this->assertSame(OrderStatus::Confirmed, $order->status);

        Notification::assertSentOnDemand(
            OrderPaymentReceived::class,
            fn (OrderPaymentReceived $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === $order->customer_email
                && $notification->order->is($order),
        );
    }

    public function test_capture_completed_resolves_payment_via_custom_id_fallback(): void
    {
        Notification::fake();

        $order = Order::factory()->create();
        $payment = $this->createPendingPayment($order, ['gateway_session_id' => 'PAYPALORDER999']);

        $resource = [
            'id' => 'CAPTURE123',
            'supplementary_data' => [
                'related_ids' => [
                    'order_id' => 'PAYPALORDER_UNKNOWN',
                ],
            ],
            'custom_id' => (string) $order->id,
        ];

        app(PaypalPaymentSyncService::class)->handleCaptureCompleted($resource);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('CAPTURE123', $payment->gateway_charge_id);
    }

    public function test_capture_completed_notifies_the_portal_user_on_both_channels(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create(['portal_user_id' => $user->id]);
        $this->createPendingPayment($order);

        app(PaypalPaymentSyncService::class)->handleCaptureCompleted([
            'id' => 'CAPTURE-ACCOUNT',
            'supplementary_data' => [
                'related_ids' => [
                    'order_id' => 'PAYPALORDER123',
                ],
            ],
            'custom_id' => (string) $order->id,
        ]);

        Notification::assertSentTo(
            $user,
            OrderPaymentReceived::class,
            fn (OrderPaymentReceived $notification, array $channels) => $channels === ['mail', 'database'],
        );
    }

    public function test_capture_completed_is_idempotent_and_does_not_renotify(): void
    {
        Notification::fake();

        $order = Order::factory()->create();
        $payment = $this->createPendingPayment($order);

        $resource = [
            'id' => 'CAPTURE123',
            'supplementary_data' => [
                'related_ids' => [
                    'order_id' => 'PAYPALORDER123',
                ],
            ],
            'custom_id' => (string) $order->id,
        ];

        $service = app(PaypalPaymentSyncService::class);

        $service->handleCaptureCompleted($resource);
        $service->handleCaptureCompleted($resource);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);

        Notification::assertSentOnDemandTimes(OrderPaymentReceived::class, 1);
    }

    public function test_capture_completed_with_no_matching_payment_is_a_no_op(): void
    {
        Notification::fake();

        $resource = [
            'id' => 'CAPTURE123',
            'supplementary_data' => [
                'related_ids' => [
                    'order_id' => 'PAYPALORDER_UNKNOWN',
                ],
            ],
        ];

        app(PaypalPaymentSyncService::class)->handleCaptureCompleted($resource);

        Notification::assertNothingSent();
    }
}
