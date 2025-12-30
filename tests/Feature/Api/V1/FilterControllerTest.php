<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns attribute filters for a category', function (): void {
    $vendor = Vendor::factory()->create();
    $category = Category::factory()->create();

    $productA = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $productB = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    ProductAttribute::query()->create([
        'product_id' => $productA->id,
        'name' => 'Finish',
        'type' => 'string',
        'value' => 'Zinc',
    ]);

    ProductAttribute::query()->create([
        'product_id' => $productB->id,
        'name' => 'Finish',
        'type' => 'string',
        'value' => 'Plain',
    ]);

    $response = $this->getJson('/api/v1/store/filters?category_id='.$category->id);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                [
                    'name',
                    'type',
                    'values',
                ],
            ],
        ])
        ->assertJsonFragment([
            'name' => 'Finish',
            'type' => 'string',
        ]);
});
