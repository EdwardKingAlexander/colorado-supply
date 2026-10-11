<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->buyer = User::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create(['is_active' => true, 'slug' => 'hex-bolt']);
    $this->company->products()->attach($this->product->id);
});

it('renders the product page from server data at the canonical URL', function () {
    $this->actingAs($this->buyer)
        ->get(route('store.product', [$this->product, 'hex-bolt']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Store/ProductDetail')
            ->where('product.id', $this->product->id)
            ->where('product.url', route('store.product', [$this->product, 'hex-bolt'], false))
        );
});

it('corrects a missing or wrong slug with a permanent redirect', function () {
    $canonical = route('store.product', [$this->product, 'hex-bolt']);

    $this->actingAs($this->buyer)->get("/store/product/{$this->product->id}")->assertRedirect($canonical)->assertStatus(301);
    $this->actingAs($this->buyer)->get("/store/product/{$this->product->id}/old-name")->assertRedirect($canonical)->assertStatus(301);
});

it('redirects a legacy slug URL when exactly one product matches', function () {
    $this->actingAs($this->buyer)
        ->get('/store/hex-bolt')
        ->assertStatus(301)
        ->assertRedirect(route('store.product', [$this->product, 'hex-bolt']));
});

it('returns not found for a legacy slug shared by two vendors instead of guessing', function () {
    $other = Product::factory()->create([
        'vendor_id' => Vendor::factory()->create()->id, 'slug' => 'hex-bolt', 'is_active' => true,
    ]);

    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get('/store/hex-bolt')
        ->assertNotFound();

    // The canonical id-based URL still reaches each one unambiguously.
    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('store.product', [$other, 'hex-bolt']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('product.id', $other->id));
});

it('hides products outside the buyer company', function () {
    $foreign = Product::factory()->create(['is_active' => true]);

    $this->actingAs($this->buyer)
        ->get(route('store.product', [$foreign, $foreign->slug]))
        ->assertNotFound();
});

it('hides inactive products from buyers', function () {
    $this->product->update(['is_active' => false]);

    $this->actingAs($this->buyer)
        ->get(route('store.product', [$this->product, 'hex-bolt']))
        ->assertNotFound();
});

it('lets admins preview inactive products', function () {
    $this->product->update(['is_active' => false]);

    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('store.product', [$this->product, 'hex-bolt']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('product.inactive', true));
});

it('requires login', function () {
    $this->get(route('store.product', [$this->product, 'hex-bolt']))->assertRedirect();
});

it('sends a bare product to the page without any empty sections', function () {
    $bare = Product::factory()->create([
        'is_active' => true, 'description' => null, 'image' => null, 'price' => null, 'list_price' => null,
        'stock' => null, 'lead_time_days' => null, 'mpn' => null, 'gtin' => null, 'country_of_origin' => null,
        'weight_g' => null, 'length_mm' => null, 'width_mm' => null, 'height_mm' => null, 'category_id' => null,
    ]);
    $this->company->products()->attach($bare->id);

    $this->actingAs($this->buyer)
        ->get(route('store.product', [$bare, $bare->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Store/ProductDetail')
            ->missing('product.description')
            ->missing('product.images')
            ->missing('product.breadcrumbs')
            ->missing('product.identifiers')
            ->missing('product.compliance')
            ->missing('product.warnings')
            ->missing('product.specs')
            ->missing('product.documents')
            ->missing('product.stock')
            ->missing('product.related')
            ->where('product.pricing', ['priced' => false])
        );
});
