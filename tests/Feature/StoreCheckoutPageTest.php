<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCheckoutPageTest extends TestCase
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

    private function createOrderForUser(User $user, Company $company): int
    {
        $product = Product::factory()->create();
        $location = Location::create(['company_id' => $company->id, 'name' => 'Main', 'slug' => 'main-'.$company->id]);

        $validated = [
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
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'billing_address' => $this->billingAddress(),
            'shipping_same_as_billing' => true,
        ];

        $order = app(StoreCheckoutService::class)->createFromCart($user, $validated);

        return $order->id;
    }

    public function test_checkout_page_renders_for_authenticated_user(): void
    {
        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
        $user = User::factory()->create(['company_id' => $company->id]);
        Location::create(['company_id' => $company->id, 'name' => 'Main', 'slug' => 'main']);

        $response = $this->actingAs($user)->get('/store/checkout');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/Checkout')
            ->has('locations')
            ->has('contact')
        );
    }

    public function test_checkout_pay_page_renders_for_order_owner(): void
    {
        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $orderId = $this->createOrderForUser($user, $company);

        $response = $this->actingAs($user)->get("/store/checkout/{$orderId}/pay");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/CheckoutPay')
            ->where('order.id', $orderId)
            ->where('order.grand_total', 21)
            ->has('order.items', 1)
        );
    }

    public function test_checkout_pay_page_is_forbidden_for_other_users(): void
    {
        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
        $userA = User::factory()->create(['company_id' => $company->id]);
        $userB = User::factory()->create(['company_id' => $company->id]);
        $orderId = $this->createOrderForUser($userA, $company);

        $response = $this->actingAs($userB)->get("/store/checkout/{$orderId}/pay");

        $response->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $orderId = $this->createOrderForUser($user, $company);

        $this->get('/store/checkout')->assertRedirect(route('login'));
        $this->get("/store/checkout/{$orderId}/pay")->assertRedirect(route('login'));
    }
}
