<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\Paypal\FakesPaypalHttp;
use Tests\TestCase;

class StorePaypalReturnControllerTest extends TestCase
{
    use FakesPaypalHttp;
    use RefreshDatabase;

    private function createPendingPayment(Order $order, string $paypalOrderId = 'PAYPALORDER123')
    {
        return $order->payments()->create([
            'method' => PaymentMethod::Paypal,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'gateway' => 'paypal',
            'gateway_session_id' => $paypalOrderId,
        ]);
    }

    public function test_return_captures_payment_and_redirects_to_success(): void
    {
        $this->fakePaypal();

        $user = User::factory()->create();
        $order = Order::factory()->create(['portal_user_id' => $user->id]);
        $payment = $this->createPendingPayment($order);

        $response = $this->actingAs($user)
            ->get("/store/checkout/{$order->id}/paypal/return?token=PAYPALORDER123");

        $response->assertRedirect(route('store.checkout.success', $order->id));

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('CAPTURE123', $payment->gateway_charge_id);
        $this->assertTrue($order->isPaid());
    }

    public function test_return_with_no_matching_payment_redirects_to_cancel(): void
    {
        $this->fakePaypal();

        $user = User::factory()->create();
        $order = Order::factory()->create(['portal_user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get("/store/checkout/{$order->id}/paypal/return?token=PAYPALORDER_UNKNOWN");

        $response->assertRedirect(route('store.checkout.cancel', $order->id));
        $response->assertSessionHas('error');
    }

    public function test_return_with_failed_capture_redirects_to_cancel(): void
    {
        $this->fakePaypal([
            '*/v2/checkout/orders/*/capture' => Http::response([
                'status' => 'FAILED',
            ], 200),
        ]);

        $user = User::factory()->create();
        $order = Order::factory()->create(['portal_user_id' => $user->id]);
        $payment = $this->createPendingPayment($order);

        $response = $this->actingAs($user)
            ->get("/store/checkout/{$order->id}/paypal/return?token=PAYPALORDER123");

        $response->assertRedirect(route('store.checkout.cancel', $order->id));
        $response->assertSessionHas('error');

        $payment->refresh();

        $this->assertSame(PaymentStatus::Pending, $payment->status);
    }

    public function test_return_is_forbidden_for_other_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $order = Order::factory()->create(['portal_user_id' => $owner->id]);
        $this->createPendingPayment($order);

        $response = $this->actingAs($other)
            ->get("/store/checkout/{$order->id}/paypal/return?token=PAYPALORDER123");

        $response->assertForbidden();
    }
}
