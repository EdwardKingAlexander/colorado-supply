<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\User;
use App\Services\Store\ProductPriceResolver;

function resolvePrice(Product $product, $viewer = null, int $quantity = 1): array
{
    return app(ProductPriceResolver::class)->resolve($product->fresh(), $viewer, $quantity);
}

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->buyer = User::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'price' => 10, 'list_price' => 14, 'gsa_price' => 8.5, 'contract_price' => 8.25,
    ]);
});

it('uses the base price when nothing else applies', function () {
    $pricing = resolvePrice($this->product, $this->buyer);

    expect($pricing['priced'])->toBeTrue()
        ->and($pricing['unit_price'])->toBe(10.0)
        ->and($pricing['source'])->toBe('base')
        ->and($pricing['list_price'])->toBe(14.0);
});

it('applies the highest tier the quantity reaches', function () {
    ProductPriceTier::factory()->for($this->product)->create(['min_quantity' => 25, 'unit_price' => 9]);
    ProductPriceTier::factory()->for($this->product)->create(['min_quantity' => 100, 'unit_price' => 8]);

    expect(resolvePrice($this->product, $this->buyer, 24)['unit_price'])->toBe(10.0)
        ->and(resolvePrice($this->product, $this->buyer, 25))->toMatchArray(['unit_price' => 9.0, 'source' => 'tier'])
        ->and(resolvePrice($this->product, $this->buyer, 250)['unit_price'])->toBe(8.0)
        ->and(resolvePrice($this->product, $this->buyer)['tiers'])->toBe([
            ['min_quantity' => 25, 'unit_price' => 9.0],
            ['min_quantity' => 100, 'unit_price' => 8.0],
        ]);
});

it('prefers the company price over tiers and base price', function () {
    ProductPriceTier::factory()->for($this->product)->create(['min_quantity' => 25, 'unit_price' => 9]);
    CompanyProduct::query()->create(['company_id' => $this->company->id, 'product_id' => $this->product->id, 'price' => 7.75]);

    $pricing = resolvePrice($this->product, $this->buyer, 50);

    expect($pricing)->toMatchArray(['unit_price' => 7.75, 'source' => 'company', 'company_price' => 7.75])
        ->and($pricing['tiers'])->toBe([]);
});

it('ignores a company link without a price', function () {
    CompanyProduct::query()->create(['company_id' => $this->company->id, 'product_id' => $this->product->id, 'price' => null]);

    expect(resolvePrice($this->product, $this->buyer)['source'])->toBe('base');
});

it('reports an unpriced product', function () {
    $this->product->update(['price' => null, 'list_price' => null]);

    $pricing = resolvePrice($this->product, $this->buyer);

    expect($pricing['priced'])->toBeFalse()
        ->and($pricing['unit_price'])->toBeNull();
});

it('treats a tier-only product as priced but has no unit price below the first tier', function () {
    $this->product->update(['price' => null]);
    ProductPriceTier::factory()->for($this->product)->create(['min_quantity' => 25, 'unit_price' => 9]);

    expect(resolvePrice($this->product, $this->buyer, 1))->toMatchArray(['priced' => true, 'unit_price' => null])
        ->and(resolvePrice($this->product, $this->buyer, 25)['unit_price'])->toBe(9.0);
});

it('hides GSA and contract prices from non-government buyers', function () {
    $pricing = resolvePrice($this->product, $this->buyer);

    expect($pricing)->not->toHaveKey('gsa_price')
        ->and($pricing)->not->toHaveKey('contract_price');
});

it('shows GSA and contract prices to government buyers and admins', function () {
    $this->company->update(['is_government_buyer' => true]);

    expect(resolvePrice($this->product, $this->buyer->fresh()))->toMatchArray(['gsa_price' => 8.5, 'contract_price' => 8.25])
        ->and(resolvePrice($this->product, Admin::factory()->create()))->toMatchArray(['gsa_price' => 8.5, 'contract_price' => 8.25]);
});

it('omits GSA and contract keys a government buyer has no value for', function () {
    $this->company->update(['is_government_buyer' => true]);
    $this->product->update(['gsa_price' => null, 'contract_price' => null]);

    expect(resolvePrice($this->product, $this->buyer->fresh()))->not->toHaveKeys(['gsa_price', 'contract_price']);
});
