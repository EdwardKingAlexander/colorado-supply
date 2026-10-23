<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Reporting\Definition\InvalidReportDefinition;
use App\Reporting\Limits;
use App\Reporting\Processing\PivotBuilder;
use Tests\Feature\Reporting\ReportingFixtures as F;

/**
 * Orders: Jan confirmed 10 + 30, Jan cancelled 5, Feb confirmed 100, Feb draft (no PO) 1.
 */
function seedPivotData(): User
{
    ['user' => $user] = F::tenant('Acme');

    foreach ([
        ['2026-01-03', OrderStatus::Confirmed, 10, 'PO-A'],
        ['2026-01-09', OrderStatus::Confirmed, 30, 'PO-A'],
        ['2026-01-20', OrderStatus::Cancelled, 5, 'PO-B'],
        ['2026-02-02', OrderStatus::Confirmed, 100, 'PO-B'],
        ['2026-02-15', OrderStatus::Draft, 1, null],
    ] as [$date, $status, $total, $po]) {
        F::order($user, $total, ['created_at' => "{$date} 10:00:00", 'status' => $status, 'po_number' => $po, 'payment_status' => PaymentStatus::Unpaid]);
    }

    return $user;
}

function monthByStatus(array $extra = []): array
{
    return [
        'dataset' => 'orders',
        'mode' => 'pivot',
        'columns' => [
            ['field' => 'order.created_at', 'bucket' => 'month', 'key' => 'month'],
            ['field' => 'order.status', 'key' => 'status'],
            ['field' => 'order.grand_total', 'aggregate' => 'sum', 'key' => 'spend', 'label' => 'Spend'],
            ['field' => 'order.grand_total', 'aggregate' => 'avg', 'key' => 'avg', 'label' => 'Average'],
        ],
        'pivot' => ['rows' => ['month'], 'column' => 'status', 'values' => ['spend']],
        ...$extra,
    ];
}

test('a pivot spreads one column across the top with labelled headers', function () {
    $user = seedPivotData();

    $result = F::run($user, monthByStatus());

    expect(array_column($result->columns, 'label'))->toBe(['Order date (Month)', 'Cancelled', 'Confirmed', 'Draft'])
        ->and($result->columns[2]['pivot'])->toBe(['column_value' => 'confirmed', 'column_label' => 'Confirmed', 'value_label' => 'Spend'])
        ->and($result->columns[2]['source'])->toBe('spend')
        ->and($result->rows)->toBe([
            ['month' => '2026-01', 'spend__0' => 5.0, 'spend__1' => 40.0, 'spend__2' => null],
            ['month' => '2026-02', 'spend__0' => null, 'spend__1' => 100.0, 'spend__2' => 1.0],
        ])
        ->and($result->totals)->toBeNull()
        ->and($result->rowCount)->toBe(2);
});

test('pivot totals are exact at every level, including averages', function () {
    $user = seedPivotData();

    $result = F::run($user, monthByStatus([
        'pivot' => ['rows' => ['month'], 'column' => 'status', 'values' => ['spend', 'avg']],
        'totals' => ['grand' => true],
    ]));

    expect(array_column($result->columns, 'key'))->toBe([
        'month', 'spend__0', 'avg__0', 'spend__1', 'avg__1', 'spend__2', 'avg__2', 'spend__total', 'avg__total',
    ])
        ->and($result->columns[1]['label'])->toBe('Cancelled · Spend')
        ->and($result->rows[0])->toBe(['month' => '2026-01', 'spend__0' => 5.0, 'avg__0' => 5.0, 'spend__1' => 40.0, 'avg__1' => 20.0, 'spend__2' => null, 'avg__2' => null, 'spend__total' => 45.0, 'avg__total' => 15.0])
        ->and($result->totals)->toBe([
            'month' => null,
            'spend__0' => 5.0, 'avg__0' => 5.0,
            'spend__1' => 140.0, 'avg__1' => 46.67,
            'spend__2' => 1.0, 'avg__2' => 1.0,
            'spend__total' => 146.0, 'avg__total' => 29.2,
        ]);
});

test('blank column values get their own column after the others', function () {
    $user = seedPivotData();

    $result = F::run($user, [
        'dataset' => 'orders',
        'mode' => 'pivot',
        'columns' => [
            ['field' => 'order.po_number', 'key' => 'po'],
            ['field' => 'order.id', 'aggregate' => 'count', 'key' => 'orders'],
        ],
        'pivot' => ['rows' => [], 'column' => 'po', 'values' => ['orders']],
        'totals' => ['grand' => true],
    ]);

    expect(array_column($result->columns, 'label'))->toBe(['PO-A', 'PO-B', '(Blank)', 'Total'])
        ->and($result->rows)->toBe([['orders__0' => 2, 'orders__1' => 2, 'orders__2' => 1, 'orders__total' => 5]]);
});

test('formulas work inside pivot cells', function () {
    $user = seedPivotData();

    $result = F::run($user, monthByStatus([
        'calculated' => [['key' => 'share', 'label' => 'Share', 'formula' => 'spend / SUM(order.grand_total) * 100']],
        'pivot' => ['rows' => ['month'], 'column' => 'status', 'values' => ['share']],
    ]));

    expect($result->rows[0]['share__1'])->toBe(100.0);
});

test('rows sort by a value total and the limit caps row groups', function () {
    $user = seedPivotData();

    $result = F::run($user, monthByStatus(['sort' => [['column' => 'spend', 'dir' => 'desc']], 'limit' => 1]));

    expect($result->rows)->toHaveCount(1)
        ->and($result->rows[0]['month'])->toBe('2026-02')
        ->and($result->rowCount)->toBe(1);
});

test('more than the column cap rolls the smallest values into an exact Other column', function () {
    ['user' => $user] = F::tenant('Acme');
    $values = Limits::MAX_PIVOT_COLUMNS + 5;

    for ($i = 1; $i <= $values; $i++) {
        // PO-1 is smallest; PO-55 largest. Two orders each so averages are checkable.
        F::order($user, $i, ['po_number' => sprintf('PO-%02d', $i)]);
        F::order($user, $i + 1, ['po_number' => sprintf('PO-%02d', $i)]);
    }

    $result = F::run($user, [
        'dataset' => 'orders',
        'mode' => 'pivot',
        'columns' => [
            ['field' => 'order.po_number', 'key' => 'po'],
            ['field' => 'order.grand_total', 'aggregate' => 'sum', 'key' => 'spend'],
            ['field' => 'order.grand_total', 'aggregate' => 'avg', 'key' => 'avg'],
        ],
        'pivot' => ['rows' => [], 'column' => 'po', 'values' => ['spend', 'avg']],
        'totals' => ['grand' => true],
    ]);

    $labels = array_values(array_unique(array_column(array_column($result->columns, 'pivot'), 'column_label')));
    $kept = Limits::MAX_PIVOT_COLUMNS - 1;
    $otherIndex = $kept;
    $rolled = $values - $kept; // PO-01 .. PO-06

    // Other holds orders totalling (1+2) + (2+3) + ... for the six smallest POs.
    $otherSpend = array_sum(array_map(fn (int $i) => $i + $i + 1, range(1, $rolled)));

    expect($labels)->toHaveCount($kept + 2)
        ->and($labels[0])->toBe(sprintf('PO-%02d', $rolled + 1))
        ->and($labels[$otherIndex])->toBe('Other')
        ->and(end($labels))->toBe('Total')
        ->and($result->rows[0]["spend__{$otherIndex}"])->toBe((float) $otherSpend)
        ->and($result->rows[0]["avg__{$otherIndex}"])->toBe(round($otherSpend / ($rolled * 2), 2))
        ->and($result->columns[2 * $otherIndex + 1]['pivot']['column_value'])->toBe(PivotBuilder::OTHER);
});

test('conditional formatting styles every cell of a pivot value', function () {
    $user = seedPivotData();

    $result = F::run($user, monthByStatus([
        'formatting' => [['column' => 'spend', 'when' => ['op' => 'gte', 'value' => 40], 'style' => 'positive']],
    ]));

    expect($result->styles)->toBe([
        0 => ['spend__1' => ['positive']],
        1 => ['spend__1' => ['positive']],
    ]);
});

test('pivot layouts are validated', function (array $pivot, string $path) {
    $user = seedPivotData();

    expect(fn () => F::run($user, monthByStatus(['pivot' => $pivot])))
        ->toThrow(fn (InvalidReportDefinition $e) => expect($e->errors())->toHaveKey($path));
})->with([
    'unknown column' => [['rows' => ['month'], 'column' => 'nope', 'values' => ['spend']], 'pivot.column'],
    'column also a row' => [['rows' => ['month', 'status'], 'column' => 'status', 'values' => ['spend']], 'pivot.column'],
    'grouping column left out' => [['rows' => [], 'column' => 'status', 'values' => ['spend']], 'pivot.rows'],
    'dimension as value' => [['rows' => ['month'], 'column' => 'status', 'values' => ['month']], 'pivot.values'],
    'no values' => [['rows' => ['month'], 'column' => 'status', 'values' => []], 'pivot.values'],
]);
