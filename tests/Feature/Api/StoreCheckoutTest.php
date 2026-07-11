<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StoreCheckoutTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_user_can_checkout_their_cart(): void
    {
        Mail::fake();

        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create();
        $location = Location::create(['company_id' => $company->id, 'name' => 'Main', 'slug' => 'main']);

        $payload = [
            'items' => [
                [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => 2,
                    'price' => 10.50,
                    'slug' => $product->slug,
                    'location_id' => $location->id,
                ],
            ],
            'contact_name' => 'Jane Buyer',
            'contact_email' => 'jane@example.com',
            'billing_address' => $this->billingAddress(),
            'shipping_same_as_billing' => true,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/store/checkout', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'order_number' => $response->json('order.order_number'),
            'portal_user_id' => $user->id,
            'company_id' => $company->id,
            'status' => OrderStatus::Draft->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'subtotal' => 21.00,
            'grand_total' => 21.00,
        ]);

        $order = Order::where('order_number', $response->json('order.order_number'))->first();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 2,
            'unit_price' => 10.50,
            'line_total' => 21.00,
        ]);

        $this->assertEquals($this->billingAddress(), $order->shipping_address);
        $this->assertEquals($this->billingAddress(), $order->billing_address);

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
            return $mail->order->is($order) && $mail->hasTo('jane@example.com');
        });
    }

    public function test_admin_can_checkout_a_test_cart_without_a_portal_user(): void
    {
        $admin = Admin::factory()->create();
        $product = Product::factory()->create();

        $payload = [
            'items' => [
                [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => 2,
                    'price' => 10.50,
                    'slug' => $product->slug,
                    'location_id' => null,
                ],
            ],
            'contact_name' => 'Admin Tester',
            'contact_email' => 'admin@example.com',
            'billing_address' => $this->billingAddress(),
            'shipping_same_as_billing' => true,
        ];

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/v1/store/checkout', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'order_number' => $response->json('order.order_number'),
            'portal_user_id' => null,
            'company_id' => null,
            'status' => OrderStatus::Draft->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'subtotal' => 21.00,
            'grand_total' => 21.00,
        ]);
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/store/checkout', [
                'items' => [],
                'contact_name' => 'Jane Buyer',
                'contact_email' => 'jane@example.com',
                'billing_address' => [
                    'line1' => '123 Main St',
                    'state' => 'CO',
                    'postal_code' => '80202',
                    'country' => 'US',
                    // missing city
                ],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['items', 'billing_address.city']);
    }

    public function test_two_users_get_separate_orders(): void
    {
        $companyA = Company::create(['name' => 'Acme Co A', 'slug' => 'acme-co-a']);
        $companyB = Company::create(['name' => 'Acme Co B', 'slug' => 'acme-co-b']);
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        $product = Product::factory()->create();

        $payload = [
            'items' => [
                [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => 1,
                    'price' => 5,
                    'slug' => $product->slug,
                ],
            ],
            'contact_name' => 'Buyer',
            'contact_email' => 'buyer@example.com',
            'billing_address' => $this->billingAddress(),
        ];

        $responseA = $this->actingAs($userA, 'sanctum')->postJson('/api/v1/store/checkout', $payload);
        $responseB = $this->actingAs($userB, 'sanctum')->postJson('/api/v1/store/checkout', $payload);

        $responseA->assertCreated();
        $responseB->assertCreated();

        $this->assertDatabaseHas('orders', [
            'order_number' => $responseA->json('order.order_number'),
            'portal_user_id' => $userA->id,
            'company_id' => $companyA->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'order_number' => $responseB->json('order.order_number'),
            'portal_user_id' => $userB->id,
            'company_id' => $companyB->id,
        ]);

        $this->assertNotEquals($responseA->json('order.order_number'), $responseB->json('order.order_number'));
    }

    public function test_shipping_address_defaults_to_billing_when_same_as_billing(): void
    {
        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create();

        $payload = [
            'items' => [
                [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => 1,
                    'price' => 5,
                    'slug' => $product->slug,
                ],
            ],
            'contact_name' => 'Buyer',
            'contact_email' => 'buyer@example.com',
            'billing_address' => $this->billingAddress(),
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/store/checkout', $payload);

        $response->assertCreated();

        $order = Order::where('order_number', $response->json('order.order_number'))->first();

        $this->assertEquals($order->billing_address, $order->shipping_address);
    }
}
