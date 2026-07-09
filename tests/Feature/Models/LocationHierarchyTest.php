<?php

use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('existing flat locations remain valid root locations', function () {
    $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);

    $location = Location::create([
        'company_id' => $company->id,
        'name' => 'Denver Warehouse',
        'slug' => 'denver-warehouse',
    ]);

    expect($location->parent_id)->toBeNull()
        ->and($location->parent)->toBeNull()
        ->and(Location::roots()->pluck('id')->all())->toContain($location->id);
});

test('a location can have child sublocations', function () {
    $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $parent = Location::create([
        'company_id' => $company->id,
        'name' => 'Denver Warehouse',
        'slug' => 'denver-warehouse',
    ]);

    $child = Location::create([
        'company_id' => $company->id,
        'parent_id' => $parent->id,
        'name' => 'Tool Crib',
        'slug' => 'tool-crib',
    ]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children()->pluck('id')->all())->toContain($child->id)
        ->and($child->display_name)->toBe('Denver Warehouse / Tool Crib');
});

test('a child location must belong to the same company as its parent', function () {
    $companyA = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $companyB = Company::create(['name' => 'Other Co', 'slug' => 'other-co']);

    $parent = Location::create([
        'company_id' => $companyA->id,
        'name' => 'Denver Warehouse',
        'slug' => 'denver-warehouse',
    ]);

    Location::create([
        'company_id' => $companyB->id,
        'parent_id' => $parent->id,
        'name' => 'Other Tool Crib',
        'slug' => 'other-tool-crib',
    ]);
})->throws(ValidationException::class, 'A sublocation must belong to the same company as its parent location.');

test('a location cannot be its own parent', function () {
    $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);

    $location = Location::create([
        'company_id' => $company->id,
        'name' => 'Denver Warehouse',
        'slug' => 'denver-warehouse',
    ]);

    $location->update(['parent_id' => $location->id]);
})->throws(ValidationException::class, 'A location cannot be its own parent.');

test('locations can be scoped to a company', function () {
    $companyA = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $companyB = Company::create(['name' => 'Other Co', 'slug' => 'other-co']);

    $locationA = Location::create([
        'company_id' => $companyA->id,
        'name' => 'Denver Warehouse',
        'slug' => 'denver-warehouse',
    ]);
    $locationB = Location::create([
        'company_id' => $companyB->id,
        'name' => 'Other Warehouse',
        'slug' => 'other-warehouse',
    ]);

    expect(Location::forCompany($companyA->id)->pluck('id')->all())
        ->toContain($locationA->id)
        ->not->toContain($locationB->id);
});

test('deleting a parent keeps child locations and historical order items', function () {
    $company = Company::create(['name' => 'Acme Co', 'slug' => 'acme-co']);
    $parent = Location::create([
        'company_id' => $company->id,
        'name' => 'Denver Warehouse',
        'slug' => 'denver-warehouse',
    ]);
    $child = Location::create([
        'company_id' => $company->id,
        'parent_id' => $parent->id,
        'name' => 'Tool Crib',
        'slug' => 'tool-crib',
    ]);
    $product = Product::factory()->create();
    $order = Order::factory()->create(['company_id' => $company->id]);

    $orderItem = $order->items()->create([
        'product_id' => $product->id,
        'location_id' => $child->id,
        'name' => $product->name,
        'quantity' => 2,
        'unit_price' => 10,
        'line_discount' => 0,
    ]);

    $parent->delete();

    expect($child->fresh()->parent_id)->toBeNull()
        ->and($orderItem->fresh()->location_id)->toBe($child->id);
});
