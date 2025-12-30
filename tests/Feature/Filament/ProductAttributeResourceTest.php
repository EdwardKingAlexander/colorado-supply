<?php

use App\Filament\Resources\ProductAttributeResource\Pages\CreateProductAttribute;
use App\Filament\Resources\ProductAttributeResource\Pages\ListProductAttributes;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs($this->user = User::factory()->create());

    $role = Role::query()->firstOrCreate(
        ['name' => 'super_admin', 'guard_name' => 'web'],
        ['name' => 'super_admin', 'guard_name' => 'web']
    );

    $this->user->assignRole($role);
});

it('lists product attributes in the admin table', function () {
    $attributes = ProductAttribute::factory()->count(3)->create();

    Livewire::test(ListProductAttributes::class)
        ->assertCanSeeTableRecords($attributes);
});

it('creates a product attribute via the form', function () {
    $product = Product::factory()->create();

    Livewire::test(CreateProductAttribute::class)
        ->fillForm([
            'product_id' => $product->id,
            'name' => 'Color',
            'type' => 'string',
            'value' => 'Blue',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('product_attributes', [
        'product_id' => $product->id,
        'name' => 'Color',
        'type' => 'string',
        'value' => 'Blue',
    ]);
});
