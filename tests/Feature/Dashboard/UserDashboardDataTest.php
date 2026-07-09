<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Dashboard\UserDashboardDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashboardOrder(User $user, float $total, array $attributes = []): Order
{
    return Order::factory()->create(array_merge([
        'portal_user_id' => $user->id,
        'company_id' => $user->company_id,
        'grand_total' => $total,
        'subtotal' => $total,
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Paid,
        'created_at' => now()->subDays(3),
    ], $attributes));
}

function dashboardOrderItem(Order $order, ?Location $location, float $lineTotal, string $name = 'Safety Gloves'): void
{
    $product = Product::factory()->create(['name' => $name]);

    $order->items()->create([
        'product_id' => $product->id,
        'location_id' => $location?->id,
        'name' => $name,
        'sku' => $product->sku ?? null,
        'quantity' => 2,
        'unit_price' => $lineTotal / 2,
        'line_discount' => 0,
    ]);
}

test('dashboard route renders for a normal user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('account')
            ->has('summary')
            ->has('charts')
            ->has('recent_orders')
            ->has('top_items'));
});

test('guest users are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('a user without a company receives an empty location state', function () {
    $user = User::factory()->create(['company_id' => null]);

    $data = app(UserDashboardDataService::class)->dataFor($user);

    expect($data['account']['company'])->toBeNull()
        ->and($data['locations'])->toBeEmpty()
        ->and($data['summary']['orders_count'])->toBe(0);
});

test('dashboard totals exclude other company data', function () {
    $companyA = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $companyB = Company::create(['name' => 'Other Co', 'slug' => 'other-co']);
    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userB = User::factory()->create(['company_id' => $companyB->id]);

    dashboardOrder($userA, 100);
    dashboardOrder($userB, 900);

    $data = app(UserDashboardDataService::class)->dataFor($userA);

    expect($data['summary']['total_spend'])->toBe(100.0)
        ->and($data['summary']['orders_count'])->toBe(1);
});

test('dashboard rolls sublocation spend up to the parent location', function () {
    $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $parent = Location::create(['company_id' => $company->id, 'name' => 'Denver', 'slug' => 'denver']);
    $child = Location::create(['company_id' => $company->id, 'parent_id' => $parent->id, 'name' => 'Tool Crib', 'slug' => 'tool-crib']);
    $order = dashboardOrder($user, 150);
    dashboardOrderItem($order, $child, 150);

    $data = app(UserDashboardDataService::class)->dataFor($user);

    expect($data['charts']['spend_by_location'][0]['location_id'])->toBe($parent->id)
        ->and($data['charts']['spend_by_location'][0]['total'])->toBe(150.0)
        ->and($data['charts']['spend_by_sublocation'][0]['location_id'])->toBe($child->id);
});

test('dashboard date filters constrain results', function () {
    $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $user = User::factory()->create(['company_id' => $company->id]);

    dashboardOrder($user, 100, ['created_at' => '2026-01-15 12:00:00']);
    dashboardOrder($user, 300, ['created_at' => '2026-03-15 12:00:00']);

    $data = app(UserDashboardDataService::class)->dataFor($user, [
        'range' => 'custom',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]);

    expect($data['summary']['total_spend'])->toBe(100.0)
        ->and($data['summary']['orders_count'])->toBe(1);
});
