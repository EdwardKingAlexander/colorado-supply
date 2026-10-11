<?php

/**
 * Fixture for tests/Browser/product-page.spec.ts.
 *
 *   E2E_TOKEN=abc E2E_PASSWORD=... php tests/Browser/support/product-page-fixture.php setup
 *   E2E_TOKEN=abc php tests/Browser/support/product-page-fixture.php teardown
 *
 * Creates a throwaway buyer (no company, so the whole catalog is visible), a
 * fully populated product, a substitute and a bare product in the database the
 * Playwright server uses, and removes them again. Prints JSON for the spec.
 * Records are tagged with E2E-PDP-{TOKEN}- SKUs and an e2e-pdp-{token} email.
 */

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductPriceTier;
use App\Models\ProductRelation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$token = preg_replace('/[^a-z0-9]/', '', strtolower((string) getenv('E2E_TOKEN'))) ?: 'local';

// Scoped to one run/worker so parallel or restarted workers never delete each other's data.
define('E2E_EMAIL', "e2e-pdp-{$token}@example.test");
define('E2E_PREFIX', 'E2E-PDP-'.strtoupper($token).'-');
define('E2E_IMAGE_DIR', "e2e-product-page/{$token}");
define('E2E_SLUG', "e2e-pdp-{$token}");

function e2eTeardown(): void
{
    Product::withoutGlobalScopes()->withTrashed()->where('sku', 'like', E2E_PREFIX.'%')->get()
        ->each(fn (Product $product) => $product->forceDelete());

    // Quotes requested by the buyer cascade away with it (quotes.sales_rep_id).
    User::query()->where('email', E2E_EMAIL)->get()->each(fn (User $user) => $user->forceDelete());

    Storage::disk('public')->deleteDirectory(E2E_IMAGE_DIR);
}

function e2eImage(string $name, array $rgb): string
{
    $image = imagecreatetruecolor(400, 400);
    imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
    ob_start();
    imagepng($image);
    $path = E2E_IMAGE_DIR."/{$name}.png";
    Storage::disk('public')->put($path, ob_get_clean());

    return $path;
}

$command = $argv[1] ?? '';

if ($command === 'teardown') {
    e2eTeardown();
    echo json_encode(['ok' => true]);
    exit(0);
}

if ($command !== 'setup' || ! getenv('E2E_PASSWORD')) {
    fwrite(STDERR, "Usage: E2E_PASSWORD=... php product-page-fixture.php setup|teardown\n");
    exit(1);
}

e2eTeardown();

User::query()->forceCreate([
    'name' => 'E2E Buyer',
    'email' => E2E_EMAIL,
    'password' => Hash::make(getenv('E2E_PASSWORD')),
    'email_verified_at' => now(),
]);

$vendor = Vendor::query()->orderBy('id')->first() ?? Vendor::factory()->create();
$category = Category::query()->whereNotNull('parent_id')->whereDoesntHave('children')->orderBy('id')->first();

$base = ['vendor_id' => $vendor->id, 'is_active' => true];

$full = Product::withoutGlobalScopes()->forceCreate($base + [
    'sku' => E2E_PREFIX.'FULL',
    'slug' => E2E_SLUG.'-full',
    'name' => 'E2E Hex Bolt, Grade 8, 1/4-20 x 1 in',
    'category_id' => $category?->id,
    'description' => str_repeat('Zinc-plated grade 8 hex cap screw for structural fastening. ', 10),
    'image' => e2eImage('main', [210, 214, 220]),
    'price' => 10, 'list_price' => 14, 'stock' => 3, 'lead_time_days' => 7,
    'unit_of_measure' => 'BX', 'minimum_order_quantity' => 5,
    'mpn' => 'MPN-E2E-1', 'part_number' => 'PN-E2E-1', 'nsn' => '5305002693238', 'cage_code' => '1ABC2',
    'standard_type' => 'MS', 'mil_spec_number' => 'MS90725-60',
    'berry_compliant' => true, 'made_in_usa' => true, 'is_hazmat' => true,
    'material' => 'Steel', 'finish' => 'Zinc', 'country_of_origin' => 'US',
    'length_mm' => 25, 'weight_g' => 120, 'unspsc' => '31161500',
    'datasheet_url' => 'https://example.com/datasheet.pdf', 'msds_url' => 'https://example.com/sds.pdf',
]);

ProductPriceTier::query()->create(['product_id' => $full->id, 'min_quantity' => 25, 'unit_price' => 9]);
ProductPriceTier::query()->create(['product_id' => $full->id, 'min_quantity' => 100, 'unit_price' => 8]);
ProductImage::query()->create(['product_id' => $full->id, 'path' => e2eImage('side', [120, 160, 200]), 'alt' => 'Side view', 'sort_order' => 1]);
ProductAttribute::query()->create(['product_id' => $full->id, 'name' => 'Thread', 'type' => 'string', 'value' => '1/4-20']);

$substitute = Product::withoutGlobalScopes()->forceCreate($base + [
    'sku' => E2E_PREFIX.'SUB', 'slug' => E2E_SLUG.'-sub', 'name' => 'E2E Hex Bolt, Grade 5', 'price' => 11,
]);
ProductRelation::query()->create(['product_id' => $full->id, 'related_product_id' => $substitute->id, 'type' => ProductRelation::SUBSTITUTE]);

$bare = Product::withoutGlobalScopes()->forceCreate($base + [
    'sku' => E2E_PREFIX.'BARE', 'slug' => E2E_SLUG.'-bare', 'name' => 'E2E Bare Washer',
]);

echo json_encode([
    'email' => E2E_EMAIL,
    'sku' => $full->sku,
    'full' => "/store/product/{$full->id}/{$full->slug}",
    'bare' => "/store/product/{$bare->id}/{$bare->slug}",
    'substitute' => "/store/product/{$substitute->id}/{$substitute->slug}",
]);
