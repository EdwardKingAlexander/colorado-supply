<?php

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Products\SampleProductCsvGenerator;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function parseSampleCsv(string $csv): array
{
    $lines = array_map('str_getcsv', preg_split('/\R/', trim($csv)));
    $header = array_shift($lines);

    return array_map(fn (array $row) => array_combine($header, $row), $lines);
}

beforeEach(function () {
    Vendor::factory()->count(3)->create();

    $parent = Category::factory()->create(['name' => 'Fasteners', 'parent_id' => null]);
    Category::factory()->create(['name' => 'Screws', 'parent_id' => $parent->id]);
    Category::factory()->create(['name' => 'Hydraulic Valves', 'parent_id' => null]);
});

it('generates the requested number of products using only existing leaf categories and vendors', function () {
    $rows = parseSampleCsv(app(SampleProductCsvGenerator::class)->generate(1000));

    $leafIds = Category::query()->whereDoesntHave('children')->pluck('id')->map(fn ($id) => (string) $id)->all();
    $vendorIds = Vendor::query()->pluck('id')->map(fn ($id) => (string) $id)->all();

    expect($rows)->toHaveCount(1000)
        ->and(collect($rows)->pluck('sku')->unique())->toHaveCount(1000)
        ->and(collect($rows)->pluck('category_id')->unique()->diff($leafIds))->toBeEmpty()
        ->and(collect($rows)->pluck('category_id')->unique()->sort()->values()->all())->toBe(collect($leafIds)->sort()->values()->all())
        ->and(collect($rows)->pluck('vendor_id')->unique()->diff($vendorIds))->toBeEmpty();
});

it('produces the same file for the same database so re-imports update instead of duplicating', function () {
    $generator = app(SampleProductCsvGenerator::class);

    expect($generator->generate(50))->toBe($generator->generate(50));
});

it('imports cleanly through the product importer', function () {
    $path = tempnam(sys_get_temp_dir(), 'sample').'.csv';
    file_put_contents($path, app(SampleProductCsvGenerator::class)->generate(1000));

    $import = new ReflectionMethod(ProductResource::class, 'importProductsFromCsv');
    $first = $import->invoke(null, $path);
    $second = $import->invoke(null, $path);
    @unlink($path);

    expect($first['errors'])->toBe([])
        ->and($first['created'])->toBe(1000)
        ->and($second['created'])->toBe(0)
        ->and(Product::query()->where('sku', 'like', 'SAMPLE-%')->count())->toBe(1000);
});

it('refuses to generate without vendors or categories', function () {
    Product::query()->forceDelete();
    Vendor::query()->delete();

    app(SampleProductCsvGenerator::class)->generate(10);
})->throws(RuntimeException::class, 'vendor');

it('downloads the sample file from the products page', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(Admin::factory()->create(), 'admin');
    $this->actingAs($user = User::factory()->create());
    $user->assignRole(Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    Livewire::test(ListProducts::class)
        ->callAction(TestAction::make('downloadSampleProducts')->table())
        ->assertFileDownloaded('sample-products-1000.csv');
});
