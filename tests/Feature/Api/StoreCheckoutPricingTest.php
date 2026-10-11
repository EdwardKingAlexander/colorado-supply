<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function checkoutPayload(array $items): array
{
    return [
        'items' => $items,
        'contact_name' => 'Jane Buyer',
        'contact_email' => 'jane@example.com',
        'billing_address' => [
            'line1' => '123 Main St', 'city' => 'Denver', 'state' => 'CO', 'postal_code' => '80202', 'country' => 'US',
        ],
        'shipping_same_as_billing' => true,
    ];
}

function cartLine(Product $product, int $quantity, float $price = 0.01): array
{
    return [
        'id' => $product->id, 'product_id' => $product->id, 'name' => 'Whatever the browser said',
        'quantity' => $quantity, 'price' => $price, 'slug' => $product->slug,
    ];
}

beforeEach(function () {
    Mail::fake();
    $this->company = Company::factory()->create();
    $this->buyer = User::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'price' => 10, 'is_active' => true, 'minimum_order_quantity' => 1, 'stock' => 3,
    ]);
    $this->company->products()->attach($this->product->id);
});

it('prices lines on the server and ignores the price sent by the browser', function () {
    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 2, 0.01)]))
        ->assertCreated();

    $order = Order::query()->latest('id')->first();

    expect((float) $order->subtotal)->toBe(20.0)
        ->and((float) $order->items->first()->unit_price)->toBe(10.0)
        ->and($order->items->first()->name)->toBe($this->product->name)
        ->and($order->items->first()->sku)->toBe($this->product->sku);
});

it('applies quantity tiers and company prices', function () {
    ProductPriceTier::factory()->for($this->product)->create(['min_quantity' => 25, 'unit_price' => 9]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 30)]))
        ->assertCreated();

    expect((float) Order::query()->latest('id')->first()->items->first()->unit_price)->toBe(9.0);

    CompanyProduct::query()->where('company_id', $this->company->id)->update(['price' => 7.5]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 30)]))
        ->assertCreated();

    expect((float) Order::query()->latest('id')->first()->items->first()->unit_price)->toBe(7.5);
});

it('allows ordering more than is in stock so it can be backordered', function () {
    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 500)]))
        ->assertCreated();

    expect((int) Order::query()->latest('id')->first()->items->first()->quantity)->toBe(500);
});

it('rejects a quantity below the minimum order', function () {
    $this->product->update(['minimum_order_quantity' => 25]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 10)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity']);
});

it('rejects products that have no price and need a quote', function () {
    $this->product->update(['price' => null]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 1)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.product_id']);
});

it('rejects products outside the buyer company catalog', function () {
    $foreign = Product::factory()->create(['price' => 5, 'is_active' => true]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($foreign, 1)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.product_id']);
});

it('rejects inactive products', function () {
    $this->product->update(['is_active' => false]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 1)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.product_id']);
});

it('prices admin test checkouts from the catalog too', function () {
    $this->actingAs(Admin::factory()->create(), 'sanctum')
        ->postJson('/api/v1/store/checkout', checkoutPayload([cartLine($this->product, 3, 999)]))
        ->assertCreated();

    expect((float) Order::query()->latest('id')->first()->subtotal)->toBe(30.0);
});
