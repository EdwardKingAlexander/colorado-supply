<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Vendor;

uses()->group('store');

test('product API includes image and inventory data', function () {
    $user = User::factory()->create();
    $vendor = Vendor::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'image' => 'products/test-image.jpg',
        'stock' => 10,
        'lead_time_days' => 5,
        'weight_g' => 100,
        'length_mm' => 50,
        'width_mm' => 30,
        'height_mm' => 20,
        'is_active' => true,
    ]);

    $response = $this->getJson("/api/v1/store/products/{$product->slug}");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'slug',
            'image',
            'stock',
            'in_stock',
            'lead_time_days',
            'dimensions' => ['weight_g', 'length_mm', 'width_mm', 'height_mm'],
            'vendor',
        ],
    ]);

    expect($response->json('data.image'))->toBe('products/test-image.jpg');
    expect($response->json('data.stock'))->toBe(10);
    expect($response->json('data.in_stock'))->toBeTrue();
    expect($response->json('data.lead_time_days'))->toBe(5);
});

test('filters API returns available attributes for category', function () {
    $user = User::factory()->create();
    $vendor = Vendor::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create();

    $product1 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $product2 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    // Add attributes to products
    ProductAttribute::create([
        'product_id' => $product1->id,
        'name' => 'Color',
        'type' => 'string',
        'value' => 'Red',
    ]);

    ProductAttribute::create([
        'product_id' => $product2->id,
        'name' => 'Color',
        'type' => 'string',
        'value' => 'Blue',
    ]);

    ProductAttribute::create([
        'product_id' => $product1->id,
        'name' => 'Size',
        'type' => 'string',
        'value' => 'Large',
    ]);

    $response = $this->getJson("/api/v1/store/filters?category_id={$category->id}");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['name', 'type', 'values'],
        ],
    ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(2); // Color and Size

    $colorFilter = collect($data)->firstWhere('name', 'Color');
    expect($colorFilter['values'])->toContain('Red', 'Blue');
});

test('products can be filtered by attributes', function () {
    $user = User::factory()->create();
    $vendor = Vendor::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create();

    $redProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Red Widget',
        'is_active' => true,
    ]);

    $blueProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Blue Widget',
        'is_active' => true,
    ]);

    ProductAttribute::create([
        'product_id' => $redProduct->id,
        'name' => 'Color',
        'type' => 'string',
        'value' => 'Red',
    ]);

    ProductAttribute::create([
        'product_id' => $blueProduct->id,
        'name' => 'Color',
        'type' => 'string',
        'value' => 'Blue',
    ]);

    // Filter for red products
    $response = $this->getJson('/api/v1/store/products?filters[Color]=Red');

    $response->assertSuccessful();
    $products = $response->json('data');

    expect($products)->toHaveCount(1);
    expect($products[0]['name'])->toBe('Red Widget');
});

test('product list shows stock status', function () {
    $user = User::factory()->create();
    $vendor = Vendor::factory()->create(['user_id' => $user->id]);

    $inStockProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'stock' => 10,
        'is_active' => true,
    ]);

    $outOfStockProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'stock' => 0,
        'lead_time_days' => 7,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/store/products');

    $response->assertSuccessful();

    $inStock = collect($response->json('data'))->firstWhere('id', $inStockProduct->id);
    $outOfStock = collect($response->json('data'))->firstWhere('id', $outOfStockProduct->id);

    expect($inStock['in_stock'])->toBeTrue();
    expect($outOfStock['in_stock'])->toBeFalse();
    expect($outOfStock['lead_time_days'])->toBe(7);
});

test('filters dynamically update based on active filters', function () {
    $user = User::factory()->create();
    $vendor = Vendor::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create();

    // Create products with different attribute combinations
    $hardHat = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Hard Hat',
        'is_active' => true,
    ]);

    $safetyGlasses = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Safety Glasses',
        'is_active' => true,
    ]);

    // Hard Hat attributes (no Anti-scratch coating)
    ProductAttribute::create([
        'product_id' => $hardHat->id,
        'name' => 'Product Type',
        'type' => 'string',
        'value' => 'Hard Hat',
    ]);

    ProductAttribute::create([
        'product_id' => $hardHat->id,
        'name' => 'Color',
        'type' => 'string',
        'value' => 'Yellow',
    ]);

    // Safety Glasses attributes (has Anti-scratch coating)
    ProductAttribute::create([
        'product_id' => $safetyGlasses->id,
        'name' => 'Product Type',
        'type' => 'string',
        'value' => 'Safety Glasses',
    ]);

    ProductAttribute::create([
        'product_id' => $safetyGlasses->id,
        'name' => 'Anti-scratch coating',
        'type' => 'string',
        'value' => 'Yes',
    ]);

    ProductAttribute::create([
        'product_id' => $safetyGlasses->id,
        'name' => 'Lens Color',
        'type' => 'string',
        'value' => 'Clear',
    ]);

    // First: Get all filters without any active filters
    $allFiltersResponse = $this->getJson("/api/v1/store/filters?category_id={$category->id}");
    $allFiltersResponse->assertSuccessful();
    $allFilters = $allFiltersResponse->json('data');

    // Should have 4 filters: Product Type, Color, Anti-scratch coating, Lens Color
    expect($allFilters)->toHaveCount(4);

    // Second: Get filters when filtering for Hard Hat
    $hardHatFiltersResponse = $this->getJson("/api/v1/store/filters?category_id={$category->id}&filters[Product Type]=Hard Hat");
    $hardHatFiltersResponse->assertSuccessful();
    $hardHatFilters = $hardHatFiltersResponse->json('data');

    // Should only have 2 filters: Product Type and Color (no Anti-scratch coating or Lens Color)
    expect($hardHatFilters)->toHaveCount(2);

    $filterNames = collect($hardHatFilters)->pluck('name')->toArray();
    expect($filterNames)->toContain('Product Type', 'Color');
    expect($filterNames)->not->toContain('Anti-scratch coating', 'Lens Color');
});
