<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Product;
use App\Models\User;
use App\Reporting\Templates\LegacyPresets;
use App\Services\Dashboard\UserDashboardReportService;
use App\Support\Dashboard\DashboardDateRange;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Reporting\ReportingFixtures as F;

/**
 * Three months of orders across locations and products, plus another
 * company's orders that must not appear in either engine.
 */
function seedParityData(): User
{
    ['user' => $user, 'location' => $hq, 'sublocation' => $annex] = F::tenant('Acme');
    ['user' => $other, 'location' => $otherHq] = F::tenant('Other');

    $gloves = Product::factory()->create(['name' => 'Gloves', 'sku' => 'GLV-1']);
    $boots = Product::factory()->create(['name' => 'Boots', 'sku' => 'BT-1']);

    $orders = [
        ['2026-01-05 10:00:00', OrderStatus::Confirmed, PaymentStatus::Paid, [[$hq, 10, 3, $gloves], [$annex, 50, 1, $boots]]],
        ['2026-01-20 10:00:00', OrderStatus::Confirmed, PaymentStatus::Unpaid, [[$annex, 10, 5, $gloves]]],
        ['2026-02-11 10:00:00', OrderStatus::Cancelled, PaymentStatus::Unpaid, [[$hq, 50, 2, $boots]]],
        ['2026-03-02 10:00:00', OrderStatus::Confirmed, PaymentStatus::Paid, [[null, 10, 1, $gloves], [$hq, 50, 1, $boots]]],
    ];

    foreach ($orders as [$date, $status, $payment, $lines]) {
        $order = F::order($user, 0, ['created_at' => $date, 'status' => $status, 'payment_status' => $payment]);

        foreach ($lines as [$location, $price, $quantity, $product]) {
            F::line($order, $location, $price, $quantity, $product);
        }

        $order->update(['grand_total' => $order->items()->sum('line_total')]);
    }

    F::line(F::order($other, 999, ['created_at' => '2026-01-10 10:00:00']), $otherHq, 999, 1, $gloves);

    return $user;
}

function legacyRows(User $user, string $groupBy, array $filters): array
{
    return app(UserDashboardReportService::class)->exportFor($user, ['group_by' => $groupBy, ...$filters])['rows']->values()->all();
}

function engineRows(User $user, string $groupBy, array $filters): array
{
    return F::run($user, LegacyPresets::definition($groupBy, $filters))->rows;
}

$quarter = ['range' => 'custom', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31'];

test('the month preset matches the original report', function (array $filters) {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        // The original service's month grouping is SQLite-only strftime(),
        // the very bug the new engine replaces, so only SQLite can compare.
        $this->markTestSkipped('The original month report only runs on SQLite.');
    }

    $user = seedParityData();
    $range = DashboardDateRange::fromFilters($filters);

    $engine = array_map(fn (array $row) => [...$row, 'period' => $range->bucketLabel($row['period'])], engineRows($user, 'month', $filters));

    expect($engine)->toEqual(legacyRows($user, 'month', $filters))->not->toBeEmpty();
})->with([
    'whole quarter' => [$quarter],
    'paid only' => [[...$quarter, 'payment_status' => 'paid']],
    'confirmed only' => [[...$quarter, 'status' => 'confirmed']],
    'february' => [['range' => 'custom', 'start_date' => '2026-02-01', 'end_date' => '2026-02-28']],
]);

test('the location, sublocation and product presets match the original report', function (string $groupBy, array $filters) {
    $user = seedParityData();

    expect(engineRows($user, $groupBy, $filters))->toEqual(legacyRows($user, $groupBy, $filters))->not->toBeEmpty();
})->with([
    'location' => ['location', $quarter],
    'sublocation' => ['sublocation', $quarter],
    'product' => ['product', $quarter],
    'location, paid' => ['location', [...$quarter, 'payment_status' => 'paid']],
    'product, confirmed' => ['product', [...$quarter, 'status' => 'confirmed']],
]);

test('the order preset matches the original report', function () use ($quarter) {
    $user = seedParityData();

    $labels = fn (array $row) => [
        ...$row,
        'status' => OrderStatus::from($row['status'])->label(),
        'payment_status' => PaymentStatus::from($row['payment_status'])->label(),
    ];

    expect(array_map($labels, engineRows($user, 'order', $quarter)))->toEqual(legacyRows($user, 'order', $quarter));
});

test('every legacy grouping produces a valid definition', function (string $groupBy) {
    $user = seedParityData();

    expect(F::run($user, LegacyPresets::definition($groupBy, ['range' => 'last_30_days', 'location_id' => 1]))->columns)->not->toBeEmpty();
})->with(LegacyPresets::GROUPINGS);
