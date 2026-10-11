<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationProduct;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductRelation;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Store\ProductPageData;

function pageData(Product $product, $viewer = null): array
{
    return app(ProductPageData::class)->build($product->fresh(), $viewer);
}

function makeLocation(Company $company, string $name): Location
{
    return Location::query()->create(['company_id' => $company->id, 'name' => $name, 'slug' => str($name)->slug()]);
}

beforeEach(function () {
    $this->admin = Admin::factory()->create();
    $this->vendor = Vendor::factory()->create(['name' => 'Brighton-Best']);
});

it('sends only the keys a bare product has', function () {
    $product = Product::factory()->create([
        'vendor_id' => $this->vendor->id, 'category_id' => null, 'name' => 'Washer', 'sku' => 'W-1', 'is_active' => true,
        'description' => null, 'image' => null, 'price' => null, 'list_price' => null, 'stock' => null,
        'lead_time_days' => null, 'mpn' => null, 'gtin' => null, 'country_of_origin' => null,
        'weight_g' => null, 'length_mm' => null, 'width_mm' => null, 'height_mm' => null,
        'unspsc' => null, 'psc_fsc' => null,
    ]);

    $data = pageData($product, $this->admin);

    expect(array_keys($data))->toEqualCanonicalizing([
        'id', 'name', 'slug', 'url', 'sku', 'vendor', 'unit_of_measure', 'minimum_order_quantity', 'pricing',
    ])
        ->and($data['pricing'])->toBe(['priced' => false])
        ->and($data['unit_of_measure'])->toBe('EA');
});

it('builds every section for a fully populated product', function () {
    $parent = Category::factory()->create(['name' => 'Fasteners', 'parent_id' => null]);
    $category = Category::factory()->create(['name' => 'Bolts', 'parent_id' => $parent->id]);

    $product = Product::factory()->create([
        'vendor_id' => $this->vendor->id, 'category_id' => $category->id, 'name' => 'Hex Bolt', 'sku' => 'HB-1',
        'description' => 'Grade 8', 'image' => 'products/main.jpg', 'is_active' => true, 'gtin' => null, 'price' => 2.5, 'stock' => 40,
        'lead_time_days' => 7, 'mpn' => 'M-1', 'part_number' => 'PN-1', 'nsn' => '5305002693238', 'cage_code' => '1ABC2',
        'standard_type' => 'MS', 'mil_spec_number' => 'MS90725-60', 'unit_of_measure' => 'BX', 'minimum_order_quantity' => 25,
        'berry_compliant' => true, 'taa_compliant' => false, 'is_hazmat' => true, 'country_of_origin' => 'US',
        'material' => 'Steel', 'finish' => 'Zinc', 'weight_g' => 454, 'length_mm' => 25, 'width_mm' => null, 'height_mm' => null,
        'datasheet_url' => 'https://example.test/ds.pdf', 'msds_url' => null, 'unspsc' => '31161500',
    ]);
    ProductAttribute::query()->create(['product_id' => $product->id, 'name' => 'Thread', 'type' => 'string', 'value' => '1/4-20']);
    ProductImage::factory()->for($product)->create(['path' => 'products/side.jpg', 'alt' => 'Side', 'sort_order' => 1]);
    $substitute = Product::factory()->create(['vendor_id' => $this->vendor->id, 'name' => 'Alt Bolt', 'price' => 3, 'is_active' => true]);
    $product->productRelations()->create(['related_product_id' => $substitute->id, 'type' => ProductRelation::SUBSTITUTE]);

    $data = pageData($product, $this->admin);

    expect($data['breadcrumbs'])->toBe([
        ['id' => $parent->id, 'name' => 'Fasteners'],
        ['id' => $category->id, 'name' => 'Bolts'],
    ])
        ->and($data['identifiers'])->toBe(['mpn' => 'M-1', 'part_number' => 'PN-1', 'nsn' => '5305002693238', 'cage_code' => '1ABC2'])
        ->and($data['government'])->toBe(['standard_type' => 'MS', 'mil_spec_number' => 'MS90725-60'])
        ->and($data['compliance'])->toBe(['berry_compliant'])
        ->and($data['warnings'])->toBe(['is_hazmat'])
        ->and($data['unit_of_measure'])->toBe('BX')
        ->and($data['minimum_order_quantity'])->toBe(25)
        ->and($data['specs']['general'])->toBe(['material' => 'Steel', 'finish' => 'Zinc', 'country_of_origin' => 'US'])
        ->and($data['specs']['dimensions'])->toBe(['length_mm' => 25, 'weight_g' => 454])
        ->and($data['specs']['attributes'])->toBe([['name' => 'Thread', 'value' => '1/4-20']])
        ->and($data['classification'])->toBe(['unspsc' => '31161500'])
        ->and($data['documents'])->toBe([['type' => 'datasheet', 'label' => 'Datasheet', 'url' => 'https://example.test/ds.pdf']])
        ->and($data['images'])->toBe([
            ['url' => '/storage/products/main.jpg', 'alt' => 'Hex Bolt'],
            ['url' => '/storage/products/side.jpg', 'alt' => 'Side'],
        ])
        ->and($data['stock'])->toBe(['in_stock' => true, 'on_hand' => 40, 'lead_time_days' => 7])
        ->and($data['related']['substitutes'][0])->toMatchArray(['id' => $substitute->id, 'name' => 'Alt Bolt'])
        ->and($data['related'])->not->toHaveKeys(['accessories', 'related'])
        ->and($data['description'])->toBe('Grade 8');
});

it('omits the standard type when it is Commercial', function () {
    $product = Product::factory()->create(['standard_type' => 'Commercial', 'mil_spec_number' => null]);

    expect(pageData($product, $this->admin))->not->toHaveKey('government');
});

it('lists stock for the buyer company locations and skips hidden ones', function () {
    $company = Company::factory()->create();
    $buyer = User::factory()->create(['company_id' => $company->id]);
    $denver = makeLocation($company, 'Denver');
    $boulder = makeLocation($company, 'Boulder');
    $otherCompanyLocation = makeLocation(Company::factory()->create(), 'Elsewhere');

    $product = Product::factory()->create(['stock' => 999, 'lead_time_days' => null]);
    $company->products()->attach($product->id);
    LocationProduct::query()->create(['location_id' => $denver->id, 'product_id' => $product->id, 'on_hand' => 12, 'visible' => true]);
    LocationProduct::query()->create(['location_id' => $boulder->id, 'product_id' => $product->id, 'on_hand' => 30, 'visible' => false]);
    LocationProduct::query()->create(['location_id' => $otherCompanyLocation->id, 'product_id' => $product->id, 'on_hand' => 500, 'visible' => true]);

    $this->actingAs($buyer);

    expect(pageData($product, $buyer)['stock'])->toBe([
        'in_stock' => true,
        'on_hand' => 12,
        'locations' => [['id' => $denver->id, 'name' => 'Denver', 'on_hand' => 12]],
    ]);
});

it('does not invent a stock section when stock is unknown', function () {
    $product = Product::factory()->create(['stock' => null, 'lead_time_days' => null]);

    expect(pageData($product, $this->admin))->not->toHaveKey('stock');
});

it('reports out of stock with lead time', function () {
    $product = Product::factory()->create(['stock' => 0, 'lead_time_days' => 10]);

    expect(pageData($product, $this->admin)['stock'])->toBe(['in_stock' => false, 'on_hand' => 0, 'lead_time_days' => 10]);
});

it('leaves inactive related products out', function () {
    $product = Product::factory()->create();
    $inactive = Product::factory()->create(['is_active' => false]);
    $product->productRelations()->create(['related_product_id' => $inactive->id, 'type' => ProductRelation::ACCESSORY]);

    expect(pageData($product, $this->admin))->not->toHaveKey('related');
});
