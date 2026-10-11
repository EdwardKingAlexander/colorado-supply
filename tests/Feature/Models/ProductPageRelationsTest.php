<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPriceTier;
use App\Models\ProductRelation;
use Illuminate\Database\QueryException;

it('orders price tiers by minimum quantity', function () {
    $product = Product::factory()->create();
    ProductPriceTier::factory()->for($product)->create(['min_quantity' => 100, 'unit_price' => 8]);
    ProductPriceTier::factory()->for($product)->create(['min_quantity' => 25, 'unit_price' => 9]);

    expect($product->priceTiers->pluck('min_quantity')->all())->toBe([25, 100]);
});

it('rejects two tiers with the same minimum quantity on one product', function () {
    $product = Product::factory()->create();
    ProductPriceTier::factory()->for($product)->create(['min_quantity' => 25]);

    ProductPriceTier::factory()->for($product)->create(['min_quantity' => 25]);
})->throws(QueryException::class);

it('orders gallery images by sort order', function () {
    $product = Product::factory()->create();
    ProductImage::factory()->for($product)->create(['path' => 'b.jpg', 'sort_order' => 2]);
    ProductImage::factory()->for($product)->create(['path' => 'a.jpg', 'sort_order' => 1]);

    expect($product->images->pluck('path')->all())->toBe(['a.jpg', 'b.jpg']);
});

it('groups related products by relation type', function () {
    $product = Product::factory()->create();
    $substitute = Product::factory()->create();
    $accessory = Product::factory()->create();
    $related = Product::factory()->create();

    $product->productRelations()->create(['related_product_id' => $substitute->id, 'type' => ProductRelation::SUBSTITUTE]);
    $product->productRelations()->create(['related_product_id' => $accessory->id, 'type' => ProductRelation::ACCESSORY]);
    $product->productRelations()->create(['related_product_id' => $related->id, 'type' => ProductRelation::RELATED]);

    expect($product->substitutes->pluck('id')->all())->toBe([$substitute->id])
        ->and($product->accessories->pluck('id')->all())->toBe([$accessory->id])
        ->and($product->relatedProducts->pluck('id')->all())->toBe([$related->id]);
});

it('refuses to relate a product to itself', function () {
    $product = Product::factory()->create();

    $product->productRelations()->create(['related_product_id' => $product->id, 'type' => ProductRelation::RELATED]);
})->throws(InvalidArgumentException::class);

it('removes tiers, images and relations when the product is force deleted', function () {
    $product = Product::factory()->create();
    $other = Product::factory()->create();
    ProductPriceTier::factory()->for($product)->create();
    ProductImage::factory()->for($product)->create();
    $product->productRelations()->create(['related_product_id' => $other->id, 'type' => ProductRelation::RELATED]);
    $other->productRelations()->create(['related_product_id' => $product->id, 'type' => ProductRelation::SUBSTITUTE]);

    $product->forceDelete();

    expect(ProductPriceTier::query()->count())->toBe(0)
        ->and(ProductImage::query()->count())->toBe(0)
        ->and(ProductRelation::query()->count())->toBe(0);
});

it('defaults companies to not being government buyers', function () {
    $company = Company::factory()->create();

    expect($company->fresh()->is_government_buyer)->toBeFalse();

    $company->update(['is_government_buyer' => true]);

    expect($company->fresh()->is_government_buyer)->toBeTrue();
});
