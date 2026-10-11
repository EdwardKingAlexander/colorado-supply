<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('product-quote');
    $this->company = Company::factory()->create();
    $this->buyer = User::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'price' => 12, 'is_active' => true, 'minimum_order_quantity' => 1, 'unit_of_measure' => 'BX',
    ]);
    $this->company->products()->attach($this->product->id);
});

function requestQuote(Product $product, array $data = [])
{
    return test()->postJson(route('store.product.quote', $product), $data + ['quantity' => 40]);
}

it('creates a draft quote for the buyer with a server-side price', function () {
    ProductPriceTier::factory()->for($this->product)->create(['min_quantity' => 25, 'unit_price' => 10]);

    $this->actingAs($this->buyer);

    $response = requestQuote($this->product, ['quantity' => 40, 'note' => 'Need by Friday', 'unit_price' => 0.01])
        ->assertCreated()
        ->assertJsonStructure(['quote_number']);

    $quote = Quote::query()->withoutGlobalScopes()->where('quote_number', $response->json('quote_number'))->sole();
    $item = $quote->items()->sole();

    expect($quote->status)->toBe('draft')
        ->and($quote->portal_user_id)->toBe($this->buyer->id)
        ->and($quote->company_id)->toBe($this->company->id)
        ->and($quote->notes)->toBe('Need by Friday')
        ->and($item->product_id)->toBe($this->product->id)
        ->and($item->sku)->toBe($this->product->sku)
        ->and((float) $item->qty)->toBe(40.0)
        ->and((float) $item->unit_price)->toBe(10.0)
        ->and((float) $item->line_total)->toBe(400.0)
        ->and($item->uom)->toBe('BX')
        ->and((float) $quote->grand_total)->toBe(400.0);
});

it('creates a zero-priced draft for a product that needs a quote', function () {
    $this->product->update(['price' => null]);
    $this->actingAs($this->buyer);

    $number = requestQuote($this->product, ['quantity' => 5])->assertCreated()->json('quote_number');

    $item = Quote::query()->withoutGlobalScopes()->where('quote_number', $number)->sole()->items()->sole();

    expect((float) $item->unit_price)->toBe(0.0);
});

it('rejects a quantity below the minimum order', function () {
    $this->product->update(['minimum_order_quantity' => 50]);
    $this->actingAs($this->buyer);

    requestQuote($this->product, ['quantity' => 10])->assertUnprocessable()->assertJsonValidationErrors(['quantity']);
});

it('returns not found for products outside the buyer company', function () {
    $foreign = Product::factory()->create(['is_active' => true]);
    $this->actingAs($this->buyer);

    requestQuote($foreign)->assertNotFound();
});

it('lets admins request a quote without a portal user', function () {
    User::factory()->create();
    $this->actingAs(Admin::factory()->create(), 'admin');

    $number = requestQuote($this->product, ['quantity' => 3])->assertCreated()->json('quote_number');

    $quote = Quote::query()->withoutGlobalScopes()->where('quote_number', $number)->sole();

    expect($quote->portal_user_id)->toBeNull()
        ->and($quote->status)->toBe('draft');
});

it('requires login', function () {
    // auth.web_or_admin redirects to login for every request type.
    requestQuote($this->product)->assertRedirect(route('login'));

    expect(Quote::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('throttles repeated requests', function () {
    $this->actingAs($this->buyer);

    foreach (range(1, 10) as $attempt) {
        requestQuote($this->product, ['quantity' => 1])->assertCreated();
    }

    requestQuote($this->product, ['quantity' => 1])->assertTooManyRequests();
});
