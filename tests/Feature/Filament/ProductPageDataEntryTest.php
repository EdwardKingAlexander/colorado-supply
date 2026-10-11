<?php

use App\Filament\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers\RelatedProductsRelationManager;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(Admin::factory()->create(), 'admin');
    $this->actingAs($user = User::factory()->create());
    $user->assignRole(Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $this->product = Product::factory()->create();
});

it('saves quantity price tiers and gallery images from the product form', function () {
    Storage::fake('public');
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->fillForm([
            'priceTiers' => [
                ['min_quantity' => 25, 'unit_price' => 9.50],
                ['min_quantity' => 100, 'unit_price' => 8.25],
            ],
            'images' => [
                ['path' => [UploadedFile::fake()->image('side.jpg')], 'alt' => 'Side view'],
                ['path' => [UploadedFile::fake()->image('top.jpg')], 'alt' => null],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $product = $this->product->fresh();

    expect($product->priceTiers->map(fn ($tier) => [$tier->min_quantity, (float) $tier->unit_price])->all())
        ->toBe([[25, 9.5], [100, 8.25]])
        ->and($product->images)->toHaveCount(2)
        ->and($product->images->pluck('sort_order')->all())->toBe([1, 2])
        ->and($product->images->first()->alt)->toBe('Side view');

    $product->images->each(function ($image) {
        expect($image->path)->toStartWith('products/gallery/');
        Storage::disk('public')->assertExists($image->path);
    });
});

it('saves the product with every optional section left blank', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->fillForm(['name' => 'Renamed Bolt'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->product->fresh()->name)->toBe('Renamed Bolt')
        ->and($this->product->priceTiers()->count())->toBe(0)
        ->and($this->product->images()->count())->toBe(0);
});

it('round-trips the government, compliance and ordering fields', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->fillForm([
            'unit_of_measure' => 'BX',
            'minimum_order_quantity' => 25,
            'lead_time_days' => 14,
            'part_number' => 'PN-77',
            'standard_type' => 'MS',
            'mil_spec_number' => 'MS90725-60',
            'nsn' => '5305-00-269-3238',
            'cage_code' => '1ABC2',
            'gsa_price' => 11.25,
            'contract_price' => 10.75,
            'berry_compliant' => true,
            'is_hazmat' => true,
            'country_of_origin' => 'US',
            'material' => 'Steel',
            'datasheet_url' => 'https://example.test/ds.pdf',
            'weight_g' => 120,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $product = $this->product->fresh();

    expect($product->unit_of_measure)->toBe('BX')
        ->and((int) $product->minimum_order_quantity)->toBe(25)
        ->and($product->standard_type)->toBe('MS')
        ->and($product->nsn)->toBe('5305002693238')
        ->and((float) $product->gsa_price)->toBe(11.25)
        ->and((bool) $product->berry_compliant)->toBeTrue()
        ->and((bool) $product->is_hazmat)->toBeTrue()
        ->and($product->datasheet_url)->toBe('https://example.test/ds.pdf')
        ->and((int) $product->weight_g)->toBe(120);
});

it('rejects an NSN that is not 13 digits', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->fillForm(['nsn' => '5305-00-12'])
        ->call('save')
        ->assertHasFormErrors(['nsn']);
});

it('links substitutes, accessories and related products through the relation manager', function () {
    $substitute = Product::factory()->create();

    Livewire::test(RelatedProductsRelationManager::class, [
        'ownerRecord' => $this->product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'related_product_id' => $substitute->id,
            'type' => ProductRelation::SUBSTITUTE,
        ])
        ->assertHasNoFormErrors();

    expect($this->product->substitutes()->pluck('products.id')->all())->toBe([$substitute->id]);
});

it('does not offer the product itself as a related product', function () {
    Livewire::test(RelatedProductsRelationManager::class, [
        'ownerRecord' => $this->product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'related_product_id' => $this->product->id,
            'type' => ProductRelation::RELATED,
        ])
        ->assertHasFormErrors(['related_product_id']);
});

it('toggles the government buyer flag on a company', function () {
    $company = Company::factory()->create();

    Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
        ->fillForm(['is_government_buyer' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($company->fresh()->is_government_buyer)->toBeTrue();
});
