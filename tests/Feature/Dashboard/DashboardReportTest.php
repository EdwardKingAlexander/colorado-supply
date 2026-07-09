<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportOrder(User $user, float $total, array $attributes = []): Order
{
    return Order::factory()->create(array_merge([
        'portal_user_id' => $user->id,
        'company_id' => $user->company_id,
        'grand_total' => $total,
        'subtotal' => $total,
        'status' => OrderStatus::Confirmed,
        'payment_status' => PaymentStatus::Paid,
        'created_at' => '2026-01-15 12:00:00',
    ], $attributes));
}

function reportItem(Order $order, ?Location $location, float $lineTotal, string $name = 'Safety Gloves'): void
{
    $product = Product::factory()->create(['name' => $name]);

    $order->items()->create([
        'product_id' => $product->id,
        'location_id' => $location?->id,
        'name' => $name,
        'sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => $lineTotal / 2,
        'line_discount' => 0,
    ]);
}

test('report page renders for normal users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard/reports')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Reports')
            ->has('filters')
            ->has('columns')
            ->has('rows'));
});

test('guests cannot access reports', function () {
    $this->get('/dashboard/reports')->assertRedirect(route('login'));
});

test('report grouping excludes cross-company data', function () {
    $companyA = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $companyB = Company::create(['name' => 'Other Co', 'slug' => 'other-co']);
    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userB = User::factory()->create(['company_id' => $companyB->id]);
    $locationA = Location::create(['company_id' => $companyA->id, 'name' => 'Denver', 'slug' => 'denver']);
    $locationB = Location::create(['company_id' => $companyB->id, 'name' => 'Other', 'slug' => 'other']);

    reportItem(reportOrder($userA, 100), $locationA, 100);
    reportItem(reportOrder($userB, 900), $locationB, 900);

    $this->actingAs($userA)
        ->get('/dashboard/reports?group_by=location&range=custom&start_date=2026-01-01&end_date=2026-01-31')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('row_count', 1)
            ->where('rows.0.location', 'Denver')
            ->where('rows.0.spend', 100));
});

test('csv export returns report rows', function () {
    $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $user = User::factory()->create(['company_id' => $company->id]);
    $location = Location::create(['company_id' => $company->id, 'name' => 'Denver', 'slug' => 'denver']);

    reportItem(reportOrder($user, 100), $location, 100);

    $response = $this->actingAs($user)
        ->get('/dashboard/reports/export?group_by=location&range=custom&start_date=2026-01-01&end_date=2026-01-31');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('location,orders,quantity,spend')
        ->and($content)->toContain('Denver,1,2,100');
});
