<?php

namespace Tests\Feature\Api;

use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\StripeClient;
use Tests\Support\Stripe\FakeStripeHttpClient;
use Tests\TestCase;

class StripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    private function fakeStripeClient(string $sessionId = 'cs_test_123', string $url = 'https://checkout.stripe.com/c/pay/cs_test_123'): void
    {
        $fakeHttpClient = new FakeStripeHttpClient([
            'post /v1/checkout/sessions' => [
                'body' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'url' => $url,
                    'mode' => 'payment',
                    'payment_intent' => null,
                ],
            ],
        ]);

        ApiRequestor::setHttpClient($fakeHttpClient);

        $this->app->instance(StripeClient::class, new StripeClient('sk_test_dummy'));
    }

    public function test_user_can_create_checkout_session_for_their_payable_order(): void
    {
        $this->fakeStripeClient();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
            'grand_total' => 199.99,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout");

        $response->assertSuccessful();
        $response->assertJson([
            'checkout_url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_admin_can_create_checkout_session_for_a_test_order(): void
    {
        $this->fakeStripeClient();

        $admin = Admin::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => null,
            'grand_total' => 199.99,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/api/v1/orders/{$order->id}/checkout");

        $response->assertSuccessful();
        $response->assertJson([
            'checkout_url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'gateway_session_id' => 'cs_test_123',
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_user_cannot_create_checkout_session_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $order = Order::factory()->create([
            'portal_user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout");

        $response->assertForbidden();
    }

    public function test_it_rejects_checkout_for_an_order_that_cannot_be_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['order']);
    }

    public function test_repeated_checkout_updates_the_existing_pending_payment(): void
    {
        $this->fakeStripeClient('cs_test_111', 'https://checkout.stripe.com/c/pay/cs_test_111');

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout")
            ->assertSuccessful();

        $this->fakeStripeClient('cs_test_222', 'https://checkout.stripe.com/c/pay/cs_test_222');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout")
            ->assertSuccessful();

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway_session_id' => 'cs_test_222',
        ]);
    }
}
