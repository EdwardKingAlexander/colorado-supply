<?php

use App\Mail\QuotePdfMail;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('lists only categories that contain products', function (): void {
    $visibleCategory = Category::factory()->create();
    $hiddenCategory = Category::factory()->create();

    Product::factory()->for($visibleCategory)->create();

    $response = $this->getJson('/api/v1/store/categories');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'parent_id'],
            ],
        ])
        ->assertJsonPath('data.0.id', $visibleCategory->id);
});

it('includes parent categories when children have products', function (): void {
    $parentCategory = Category::factory()->create();
    $childCategory = Category::factory()->create([
        'parent_id' => $parentCategory->id,
    ]);
    $emptyChild = Category::factory()->create([
        'parent_id' => $parentCategory->id,
    ]);

    Product::factory()->for($childCategory)->create();

    $response = $this->getJson('/api/v1/store/categories');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'parent_id'],
            ],
        ])
        ->assertJsonFragment(['id' => $parentCategory->id, 'parent_id' => null])
        ->assertJsonFragment(['id' => $childCategory->id, 'parent_id' => $parentCategory->id])
        ->assertJsonMissing(['id' => $emptyChild->id]);
});

it('paginates active products for the catalog', function (): void {
    Product::factory()->count(30)->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]); // should be hidden

    $response = $this->getJson('/api/v1/store/products');

    $response->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 30);
});

it('filters products by category id', function (): void {
    $category = Category::factory()->create();
    Product::factory()->count(2)->create([
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    Product::factory()->create(['category_id' => null, 'is_active' => true]);

    $response = $this->getJson('/api/v1/store/products?category_id='.$category->id);

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('shows individual products by slug', function (): void {
    $product = Product::factory()->create([
        'name' => 'Precision Valve',
        'slug' => 'precision-valve',
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/store/products/'.$product->slug);

    $response->assertOk()
        ->assertJsonPath('data.slug', 'precision-valve')
        ->assertJsonPath('data.name', 'Precision Valve');
});

it('searches the catalog by keyword', function (): void {
    Product::factory()->create([
        'name' => 'Stainless Bolt Assembly',
        'slug' => 'stainless-bolt',
        'description' => 'High-strength stainless steel bolt for harsh environments.',
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Copper Washer',
        'slug' => 'copper-washer',
        'description' => 'Non-matching record',
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/store/search?query=stainless');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'stainless-bolt');
});

it('generates a pdf quote for submitted items', function (): void {
    $payload = [
        'customer' => [
            'name' => 'Jane Buyer',
            'email' => 'jane@example.com',
        ],
        'items' => [
            [
                'name' => 'Safety Glasses',
                'quantity' => 3,
                'price' => 24.5,
            ],
            [
                'name' => 'Hex Bolts',
                'quantity' => 10,
                'price' => 1.1,
            ],
        ],
        'tax' => 0,
    ];

    $response = $this->postJson('/api/v1/store/quote', $payload);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
    expect($response->getContent())->not->toBeEmpty();

    $quote = Quote::query()->with('items')->latest()->first();
    expect($quote)->not->toBeNull();
    expect($quote->items)->toHaveCount(2);
});

it('emails a quote when delivery is email', function (): void {
    Mail::fake();

    $payload = [
        'customer' => [
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
        ],
        'items' => [
            [
                'name' => 'Industrial Fan',
                'quantity' => 1,
                'price' => 325.75,
            ],
        ],
        'delivery' => 'email',
    ];

    $response = $this->postJson('/api/v1/store/quote', $payload);

    $response->assertOk()
        ->assertJson(['message' => 'Quote emailed successfully.'])
        ->assertJsonStructure(['quote_id', 'quote_number']);

    Mail::assertSent(QuotePdfMail::class, function (QuotePdfMail $mail) {
        return ($mail->quote['customer']['email'] ?? null) === 'buyer@example.com';
    });

    $quote = Quote::query()->with('items')->findOrFail($response->json('quote_id'));
    expect($quote->items)->toHaveCount(1);
});
