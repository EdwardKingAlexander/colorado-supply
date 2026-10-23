<?php

/**
 * Fixture for tests/Browser/will-call.spec.ts.
 *
 *   E2E_TOKEN=abc E2E_PASSWORD=... php tests/Browser/support/will-call-fixture.php setup
 *   E2E_TOKEN=abc php tests/Browser/support/will-call-fixture.php otp
 *   E2E_TOKEN=abc php tests/Browser/support/will-call-fixture.php teardown
 *
 * Creates a throwaway buyer (no company, so every pickup counter is offered),
 * an admin enrolled in app MFA with the super_admin role, a pickup counter and
 * an in-stock product, and removes them again together with the orders placed
 * at that counter and every notification that mentions those orders (admin
 * alerts go to every admin). `otp` prints the admin's current MFA code and
 * `order` the latest order placed at the counter.
 * Records are tagged e2e-wc-{token} / E2E-WC-{TOKEN}.
 */

use App\Models\Admin;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$token = preg_replace('/[^a-z0-9]/', '', strtolower((string) getenv('E2E_TOKEN'))) ?: 'local';

// Scoped to one run/worker so parallel or restarted workers never delete each other's data.
define('E2E_BUYER_EMAIL', "e2e-wc-{$token}@example.test");
define('E2E_ADMIN_EMAIL', "e2e-wc-admin-{$token}@example.test");
define('E2E_PREFIX', 'E2E-WC-'.strtoupper($token));
define('E2E_SLUG', "e2e-wc-{$token}");

function e2eTeardown(): void
{
    $locations = Location::query()->where('slug', E2E_SLUG)->pluck('id');
    $buyer = User::query()->where('email', E2E_BUYER_EMAIL)->first();

    $orders = Order::withoutGlobalScopes()->withTrashed()
        ->where(fn ($query) => $query->whereIn('pickup_location_id', $locations)->orWhere('portal_user_id', $buyer?->id ?? 0))
        ->get();

    foreach ($orders as $order) {
        DatabaseNotification::query()->where('data', 'like', '%'.$order->order_number.'%')->delete();
        $order->items()->delete();
        $order->forceDelete();
    }

    if ($buyer) {
        $buyer->notifications()->delete();
        $buyer->forceDelete();
    }

    Admin::query()->where('email', E2E_ADMIN_EMAIL)->get()->each(function (Admin $admin) {
        $admin->notifications()->delete();
        $admin->syncRoles([]);
        $admin->delete();
    });

    Product::withoutGlobalScopes()->withTrashed()->where('sku', E2E_PREFIX)->get()
        ->each(fn (Product $product) => $product->forceDelete());

    Location::query()->whereIn('id', $locations)->delete();
}

$command = $argv[1] ?? '';

if ($command === 'teardown') {
    e2eTeardown();
    echo json_encode(['ok' => true]);
    exit(0);
}

if ($command === 'otp') {
    $admin = Admin::query()->where('email', E2E_ADMIN_EMAIL)->firstOrFail();
    echo app(Google2FA::class)->getCurrentOtp($admin->getAppAuthenticationSecret());
    exit(0);
}

if ($command === 'order') {
    $order = Order::withoutGlobalScopes()
        ->whereIn('pickup_location_id', Location::query()->where('slug', E2E_SLUG)->select('id'))
        ->latest('id')
        ->firstOrFail();
    echo json_encode(['id' => $order->id, 'number' => $order->order_number, 'pickupCode' => $order->pickup_code]);
    exit(0);
}

if ($command !== 'setup' || ! getenv('E2E_PASSWORD')) {
    fwrite(STDERR, "Usage: E2E_PASSWORD=... php will-call-fixture.php setup|otp|order|teardown\n");
    exit(1);
}

e2eTeardown();

$password = Hash::make(getenv('E2E_PASSWORD'));

User::query()->forceCreate([
    'name' => 'E2E Will Call Buyer',
    'email' => E2E_BUYER_EMAIL,
    'password' => $password,
    'email_verified_at' => now(),
]);

$admin = Admin::query()->forceCreate([
    'name' => 'E2E Will Call Admin',
    'email' => E2E_ADMIN_EMAIL,
    'password' => $password,
    'app_authentication_secret' => app(Google2FA::class)->generateSecretKey(),
]);
$admin->assignRole(Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

$location = Location::query()->forceCreate([
    'company_id' => (Company::query()->orderBy('id')->first() ?? Company::factory()->create())->id,
    'name' => 'E2E Will Call Counter '.strtoupper($token),
    'slug' => E2E_SLUG,
    'pickup_enabled' => true,
    'address' => ['line1' => '100 Supply Way', 'city' => 'Denver', 'state' => 'CO', 'postal_code' => '80216'],
    'phone' => '303-555-0100',
    'hours' => 'Mon-Fri 7am-5pm',
    'pickup_instructions' => 'Use the will-call door on the north side.',
]);

$product = Product::withoutGlobalScopes()->forceCreate([
    'vendor_id' => (Vendor::query()->orderBy('id')->first() ?? Vendor::factory()->create())->id,
    'is_active' => true,
    'sku' => E2E_PREFIX,
    'slug' => E2E_SLUG,
    'name' => 'E2E Will Call Hex Nut',
    'price' => 12,
    'stock' => 500,
]);

echo json_encode([
    'buyerEmail' => E2E_BUYER_EMAIL,
    'adminEmail' => E2E_ADMIN_EMAIL,
    'locationId' => $location->id,
    'locationName' => $location->name,
    'cartItem' => [
        'id' => $product->id,
        'productId' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'slug' => $product->slug,
        'price' => 12,
        'quantity' => 2,
    ],
]);
