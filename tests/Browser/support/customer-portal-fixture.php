<?php

/**
 * Fixture for tests/Browser/customer-portal.spec.ts.
 *
 *   E2E_TOKEN=abc E2E_PASSWORD=... php tests/Browser/support/customer-portal-fixture.php setup
 *   E2E_TOKEN=abc php tests/Browser/support/customer-portal-fixture.php teardown
 *
 * Creates a throwaway company with two locations and a verified buyer who
 * belongs to it, and removes them again. Records are tagged e2e-cp-{token}.
 */

use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$token = preg_replace('/[^a-z0-9]/', '', strtolower((string) getenv('E2E_TOKEN'))) ?: 'local';

define('E2E_BUYER_EMAIL', "e2e-cp-{$token}@example.test");
define('E2E_SLUG', "e2e-cp-{$token}");

function e2eTeardown(): void
{
    $buyer = User::query()->where('email', E2E_BUYER_EMAIL)->first();

    if ($buyer) {
        $buyer->notifications()->delete();
        $buyer->forceDelete();
    }

    $company = Company::query()->where('slug', E2E_SLUG)->first();

    if ($company) {
        Location::query()->where('company_id', $company->id)->delete();
        $company->delete();
    }
}

$command = $argv[1] ?? '';

if ($command === 'teardown') {
    e2eTeardown();
    echo json_encode(['ok' => true]);
    exit(0);
}

if ($command !== 'setup' || ! getenv('E2E_PASSWORD')) {
    fwrite(STDERR, "Usage: E2E_PASSWORD=... php customer-portal-fixture.php setup|teardown\n");
    exit(1);
}

e2eTeardown();

$company = Company::query()->forceCreate([
    'name' => 'E2E Portal Company '.strtoupper($token),
    'slug' => E2E_SLUG,
]);

foreach (['North Yard', 'South Plant'] as $index => $name) {
    Location::query()->forceCreate([
        'company_id' => $company->id,
        'name' => "{$name} ".strtoupper($token),
        'slug' => E2E_SLUG."-{$index}",
    ]);
}

User::query()->forceCreate([
    'name' => 'E2E Portal Buyer',
    'email' => E2E_BUYER_EMAIL,
    'password' => Hash::make(getenv('E2E_PASSWORD')),
    'email_verified_at' => now(),
    'company_id' => $company->id,
]);

echo json_encode(['buyerEmail' => E2E_BUYER_EMAIL]);
