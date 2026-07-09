<?php

namespace Tests\Feature\Api;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\Paypal\FakesPaypalHttp;
use Tests\TestCase;

class PaypalCheckoutTest extends TestCase
{
    use FakesPaypalHttp;
    use RefreshDatabase;

    public function test_user_can_create_paypal_order_for_their_payable_order(): void
    {
        $this->fakePaypal();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
            'grand_total' => 199.99,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout/paypal");

        $response->assertSuccessful();
        $response->assertJson([
            'approve_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPALORDER123',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'paypal',
            'gateway_session_id' => 'PAYPALORDER123',
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_user_cannot_create_paypal_order_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $order = Order::factory()->create([
            'portal_user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout/paypal");

        $response->assertForbidden();
    }

    public function test_it_rejects_paypal_checkout_for_an_order_that_cannot_be_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout/paypal");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['order']);
    }

    public function test_repeated_paypal_checkout_updates_the_existing_pending_payment(): void
    {
        // Two calls to the *same* PayPal order-creation endpoint need two
        // different responses in sequence. Calling fakePaypal() twice does
        // NOT achieve this: Http::fake() accumulates stub registrations
        // across calls and resolves by taking the *first* registered match,
        // so a later "override" for a URL pattern already faked earlier can
        // never actually win. Http::fakeSequence() is the correct tool for
        // "same endpoint, different response per call".
        Http::fake([
            '*/v1/oauth2/token' => Http::response([
                'access_token' => 'A21AAtest_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 32400,
            ], 200),
        ]);

        Http::fakeSequence('*/v2/checkout/orders')
            ->push([
                'id' => 'PAYPALORDER123',
                'status' => 'CREATED',
                'links' => [
                    ['href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPALORDER123', 'rel' => 'approve', 'method' => 'GET'],
                ],
            ], 201)
            ->push([
                'id' => 'PAYPALORDER456',
                'status' => 'CREATED',
                'links' => [
                    ['href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPALORDER456', 'rel' => 'approve', 'method' => 'GET'],
                ],
            ], 201);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout/paypal")
            ->assertSuccessful();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout/paypal")
            ->assertSuccessful();

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway_session_id' => 'PAYPALORDER456',
        ]);
    }
}
