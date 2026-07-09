<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Stripe\ApiRequestor;
use Stripe\StripeClient;
use Tests\Support\Paypal\FakesPaypalHttp;
use Tests\Support\Stripe\FakeStripeHttpClient;
use Tests\TestCase;

class StoreCheckoutFlowTest extends TestCase
{
    use FakesPaypalHttp;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    private function billingAddress(): array
    {
        return [
            'line1' => '123 Main St',
            'line2' => null,
            'city' => 'Denver',
            'state' => 'CO',
            'postal_code' => '80202',
            'country' => 'US',
        ];
    }

    private function checkoutCart(User $user, Product $product, Location $location): array
    {
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/store/checkout', [
            'items' => [
                [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => 1,
                    'price' => 42.00,
                    'slug' => $product->slug,
                    'location_id' => $location->id,
                ],
            ],
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'billing_address' => $this->billingAddress(),
            'shipping_same_as_billing' => true,
        ])->assertCreated();

        return $response->json('order');
    }

    private function eventPayload(string $type, array $object, string $id = 'evt_test_flow'): string
    {
        return json_encode([
            'id' => $id,
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $object],
        ]);
    }

    private function stripeSignatureHeader(string $payload): string
    {
        $secret = config('services.stripe.webhook_secret');
        $timestamp = time();

        return 't='.$timestamp.',v1='.hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
    }

    public function test_full_stripe_checkout_path_from_cart_to_paid(): void
    {
        Notification::fake();

        $fakeHttpClient = new FakeStripeHttpClient([
            'post /v1/checkout/sessions' => [
                'body' => [
                    'id' => 'cs_flow_test',
                    'object' => 'checkout.session',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_flow_test',
                    'mode' => 'payment',
                    'payment_intent' => 'pi_flow_test',
                ],
            ],
        ]);
        ApiRequestor::setHttpClient($fakeHttpClient);
        $this->app->instance(StripeClient::class, new StripeClient('sk_test_dummy'));

        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co-stripe-flow']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create();
        $location = Location::create(['company_id' => $company->id, 'name' => 'Main', 'slug' => 'main-stripe-flow']);

        $orderData = $this->checkoutCart($user, $product, $location);
        $order = Order::find($orderData['id']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout")
            ->assertSuccessful()
            ->assertJson(['checkout_url' => 'https://checkout.stripe.com/c/pay/cs_flow_test']);

        $payload = $this->eventPayload('payment_intent.succeeded', [
            'id' => 'pi_flow_test',
            'object' => 'payment_intent',
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $this->call('POST', '/api/v1/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignatureHeader($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertSuccessful();

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
    }

    public function test_full_paypal_checkout_path_from_cart_to_paid(): void
    {
        Notification::fake();
        $this->fakePaypal();

        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co-paypal-flow']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create();
        $location = Location::create(['company_id' => $company->id, 'name' => 'Main', 'slug' => 'main-paypal-flow']);

        $orderData = $this->checkoutCart($user, $product, $location);
        $order = Order::find($orderData['id']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout/paypal")
            ->assertSuccessful()
            ->assertJson(['approve_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPALORDER123']);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'paypal',
            'gateway_session_id' => 'PAYPALORDER123',
            'status' => PaymentStatus::Pending->value,
        ]);

        // Customer approves on PayPal and is redirected back with ?token=<paypal_order_id>.
        // Must explicitly pass the 'web' guard here: the earlier actingAs($user, 'sanctum')
        // call also switched Laravel's *default* auth guard to 'sanctum' for the rest of the
        // test (Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication::be()),
        // so a bare actingAs($user) would re-authenticate on 'sanctum' again, not 'web' —
        // the same gotcha already documented in this module's Phase 3 STATE.md log.
        $this->actingAs($user, 'web')
            ->get("/store/checkout/{$order->id}/paypal/return?token=PAYPALORDER123")
            ->assertRedirect(route('store.checkout.success', ['order' => $order->id]));

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
    }

    public function test_checkout_with_an_empty_cart_is_rejected(): void
    {
        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co-empty-cart']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/store/checkout', [
                'items' => [],
                'contact_name' => $user->name,
                'contact_email' => $user->email,
                'billing_address' => $this->billingAddress(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_already_paid_order_is_rejected_by_both_gateways(): void
    {
        $this->fakePaypal();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'portal_user_id' => $user->id,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/checkout/paypal")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    }
}
