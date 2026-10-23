<?php

use App\Models\Product;
use App\Models\User;
use App\Reporting\Definition\InvalidReportDefinition;
use Tests\Feature\Reporting\ReportingFixtures as F;

/**
 * Lines: HQ 10×3 + 20×1 (order A, Jan), Annex 5×4 (order B, Jan), HQ 100×1 (order C, Feb).
 */
function seedSummaryData(): User
{
    ['user' => $user, 'location' => $hq, 'sublocation' => $annex] = F::tenant('Acme');
    $berry = Product::factory()->create(['berry_compliant' => true]);

    $a = F::order($user, 50, ['created_at' => '2026-01-05 10:00:00']);
    F::line($a, $hq, 10, 3, $berry);
    F::line($a, $hq, 20, 1);
    F::line(F::order($user, 20, ['created_at' => '2026-01-25 10:00:00']), $annex, 5, 4, $berry);
    F::line(F::order($user, 100, ['created_at' => '2026-02-14 10:00:00']), $hq, 100, 1);

    return $user;
}

function linesSummary(array $columns, array $extra = []): array
{
    return ['dataset' => 'order_lines', 'mode' => 'summary', 'columns' => $columns, ...$extra];
}

test('summary mode groups by every non-aggregated column', function () {
    $user = seedSummaryData();

    $result = F::run($user, linesSummary([
        ['field' => 'order.created_at', 'bucket' => 'month', 'key' => 'month'],
        ['field' => 'location.sublocation_name', 'key' => 'sub'],
        ['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend'],
        ['field' => 'line.quantity', 'aggregate' => 'sum', 'key' => 'qty'],
        ['field' => 'line.unit_price', 'aggregate' => 'avg', 'key' => 'avg_price'],
        ['field' => 'line.unit_price', 'aggregate' => 'min', 'key' => 'min_price'],
        ['field' => 'line.unit_price', 'aggregate' => 'max', 'key' => 'max_price'],
        ['field' => 'line.id', 'aggregate' => 'count', 'key' => 'lines'],
        ['field' => 'order.id', 'aggregate' => 'count_distinct', 'key' => 'orders'],
        ['field' => 'product.berry_compliant', 'aggregate' => 'count_true', 'key' => 'berry'],
        ['field' => 'order.created_at', 'aggregate' => 'max', 'key' => 'last'],
    ], ['sort' => [['column' => 'month'], ['column' => 'sub']]]));

    expect($result->rows)->toEqual([
        ['month' => '2026-01', 'sub' => 'Acme Annex', 'spend' => 20.0, 'qty' => 4, 'avg_price' => 5.0, 'min_price' => 5.0, 'max_price' => 5.0, 'lines' => 1, 'orders' => 1, 'berry' => 1, 'last' => '2026-01-25 10:00:00'],
        ['month' => '2026-01', 'sub' => 'Primary', 'spend' => 50.0, 'qty' => 4, 'avg_price' => 15.0, 'min_price' => 10.0, 'max_price' => 20.0, 'lines' => 2, 'orders' => 1, 'berry' => 1, 'last' => '2026-01-05 10:00:00'],
        ['month' => '2026-02', 'sub' => 'Primary', 'spend' => 100.0, 'qty' => 1, 'avg_price' => 100.0, 'min_price' => 100.0, 'max_price' => 100.0, 'lines' => 1, 'orders' => 1, 'berry' => 0, 'last' => '2026-02-14 10:00:00'],
    ])->and($result->columns[2]['label'])->toBe('Line total (Total)')
        ->and($result->columns[2]['role'])->toBe('measure')
        ->and($result->columns[0]['role'])->toBe('dimension');
});

test('a summary with only totals returns one row', function () {
    $user = seedSummaryData();

    $result = F::run($user, linesSummary([['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend']]));

    expect($result->rows)->toBe([['spend' => 170.0]])->and($result->rowCount)->toBe(1);
});

test('grand totals are computed from all data, not by adding up groups', function () {
    $user = seedSummaryData();

    $result = F::run($user, linesSummary([
        ['field' => 'location.parent_name', 'key' => 'location'],
        ['field' => 'line.unit_price', 'aggregate' => 'avg', 'key' => 'avg_price'],
        ['field' => 'order.id', 'aggregate' => 'count_distinct', 'key' => 'orders'],
        ['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend'],
    ], ['totals' => ['grand' => true], 'limit' => 1, 'sort' => [['column' => 'spend', 'dir' => 'desc']]]));

    // Average of the four unit prices (10, 20, 5, 100), not of group averages.
    expect($result->totals)->toBe(['location' => null, 'avg_price' => 33.75, 'orders' => 3, 'spend' => 170.0])
        ->and($result->rows)->toHaveCount(1);
});

test('subtotals group by the first grouping column', function () {
    $user = seedSummaryData();

    $result = F::run($user, linesSummary([
        ['field' => 'order.created_at', 'bucket' => 'month', 'key' => 'month'],
        ['field' => 'location.sublocation_name', 'key' => 'sub'],
        ['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend'],
    ], ['totals' => ['subtotals' => true, 'grand' => false]]));

    expect($result->subtotals)->toBe([
        ['month' => '2026-01', 'sub' => null, 'spend' => 70.0],
        ['month' => '2026-02', 'sub' => null, 'spend' => 100.0],
    ])->and($result->totals)->toBeNull();
});

test('formulas use aggregates, columns and other formulas, and recompute on totals', function () {
    $user = seedSummaryData();

    $result = F::run($user, linesSummary([
        ['field' => 'location.parent_name', 'key' => 'location'],
        ['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend'],
    ], [
        'calculated' => [
            ['key' => 'per_unit', 'label' => 'Per unit', 'formula' => 'spend / SUM(line.quantity)', 'format' => 'currency'],
            ['key' => 'doubled', 'label' => 'Doubled', 'formula' => 'ROUND(per_unit * 2, 1)'],
            ['key' => 'big', 'label' => 'Big', 'formula' => 'IF(spend > 100, 1, 0)'],
            ['key' => 'safe', 'label' => 'Safe', 'formula' => 'COALESCE(spend / 0, -1)'],
        ],
        'totals' => ['grand' => true],
    ]));

    // Every line rolls up to Acme HQ (the annex is its sublocation): 170 spend over 9 units.
    expect($result->rows)->toBe([
        ['location' => 'Acme HQ', 'spend' => 170.0, 'per_unit' => 18.89, 'doubled' => 37.8, 'big' => 1.0, 'safe' => -1.0],
    ])
        ->and($result->totals['per_unit'])->toBe(18.89)
        ->and(collect($result->columns)->firstWhere('key', 'per_unit'))->toMatchArray(['role' => 'calculated', 'format' => 'currency']);
});

test('table formulas can use raw field values', function () {
    $user = seedSummaryData();

    $result = F::run($user, [
        'dataset' => 'order_lines',
        'columns' => [['field' => 'line.line_total', 'key' => 'total']],
        'calculated' => [['key' => 'check', 'label' => 'Check', 'formula' => 'line.quantity * line.unit_price - total']],
        'sort' => [['column' => 'total']],
    ]);

    expect(F::column($result, 'check'))->toBe([0.0, 0.0, 0.0, 0.0])
        ->and(array_keys($result->rows[0]))->toBe(['total', 'check']);
});

test('sorting by a calculated column happens after the formulas run', function () {
    $user = seedSummaryData();

    $result = F::run($user, linesSummary([
        ['field' => 'location.sublocation_name', 'key' => 'sub'],
        ['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend'],
    ], [
        'calculated' => [['key' => 'negative', 'label' => 'Negative', 'formula' => '0 - spend']],
        'sort' => [['column' => 'negative', 'dir' => 'asc']],
    ]));

    expect(F::column($result, 'sub'))->toBe(['Primary', 'Acme Annex']);
});

test('post-processors add derived columns next to their source', function () {
    $user = seedSummaryData();

    $result = F::run($user, [
        'dataset' => 'orders',
        'mode' => 'summary',
        'columns' => [
            ['field' => 'order.created_at', 'bucket' => 'day', 'key' => 'day'],
            ['field' => 'order.grand_total', 'aggregate' => 'sum', 'key' => 'spend'],
        ],
        'post' => [
            ['column' => 'spend', 'op' => 'percent_of_total'],
            ['column' => 'spend', 'op' => 'running_total'],
            ['column' => 'spend', 'op' => 'change_vs_previous'],
            ['column' => 'spend', 'op' => 'percent_change_vs_previous', 'key' => 'growth'],
            ['column' => 'spend', 'op' => 'rank'],
        ],
        'sort' => [['column' => 'day']],
        'totals' => ['grand' => true],
    ]);

    expect(array_column($result->columns, 'key'))->toBe([
        'day', 'spend', 'spend_percent_of_total', 'spend_running_total', 'spend_change_vs_previous', 'growth', 'spend_rank',
    ])->and($result->rows)->toBe([
        ['day' => '2026-01-05', 'spend' => 50.0, 'spend_percent_of_total' => 0.294118, 'spend_running_total' => 50.0, 'spend_change_vs_previous' => null, 'growth' => null, 'spend_rank' => 2],
        ['day' => '2026-01-25', 'spend' => 20.0, 'spend_percent_of_total' => 0.117647, 'spend_running_total' => 70.0, 'spend_change_vs_previous' => -30.0, 'growth' => -0.6, 'spend_rank' => 3],
        ['day' => '2026-02-14', 'spend' => 100.0, 'spend_percent_of_total' => 0.588235, 'spend_running_total' => 170.0, 'spend_change_vs_previous' => 80.0, 'growth' => 4.0, 'spend_rank' => 1],
    ])->and($result->totals['spend_percent_of_total'])->toBe(1.0)
        ->and($result->totals['spend_running_total'])->toBeNull()
        ->and(collect($result->columns)->firstWhere('key', 'growth')['format'])->toBe('percent');
});

test('percent of total divides by all data even when the limit cuts rows', function () {
    $user = seedSummaryData();

    $result = F::run($user, [
        'dataset' => 'orders',
        'mode' => 'summary',
        'columns' => [['field' => 'order.order_number', 'key' => 'number'], ['field' => 'order.grand_total', 'aggregate' => 'sum', 'key' => 'spend']],
        'post' => [['column' => 'spend', 'op' => 'percent_of_total', 'key' => 'share']],
        'sort' => [['column' => 'spend', 'dir' => 'desc']],
        'limit' => 1,
    ]);

    expect($result->rows)->toHaveCount(1)
        ->and($result->rows[0]['share'])->toBe(0.588235);
});

test('the preview cap applies after cross-row calculations', function () {
    $user = seedSummaryData();

    $result = F::run($user, [
        'dataset' => 'order_lines',
        'columns' => [['field' => 'line.line_total', 'key' => 'total']],
        'post' => [['column' => 'total', 'op' => 'running_total', 'key' => 'running']],
        'sort' => [['column' => 'total']],
    ], maxRows: 2);

    expect($result->rows)->toBe([['total' => 20.0, 'running' => 20.0], ['total' => 20.0, 'running' => 40.0]])
        ->and($result->rowCount)->toBe(4)
        ->and($result->truncated)->toBeTrue();
});

test('table totals sum number columns across all matching rows', function () {
    $user = seedSummaryData();

    $result = F::run($user, [
        'dataset' => 'order_lines',
        'columns' => [['field' => 'line.sku', 'key' => 'sku'], ['field' => 'line.quantity', 'key' => 'qty'], ['field' => 'line.line_total', 'key' => 'total']],
        'totals' => ['grand' => true],
        'limit' => 2,
    ]);

    expect($result->rows)->toHaveCount(2)
        ->and($result->totals)->toEqual(['sku' => null, 'qty' => 9, 'total' => 170.0]);
});

test('conditional formatting marks matching cells', function () {
    $user = seedSummaryData();

    $result = F::run($user, [
        'dataset' => 'order_lines',
        'columns' => [['field' => 'line.line_total', 'key' => 'total'], ['field' => 'location.sublocation_name', 'key' => 'sub']],
        'formatting' => [
            ['column' => 'total', 'when' => ['op' => 'top_n', 'value' => 1], 'style' => 'positive'],
            ['column' => 'total', 'when' => ['op' => 'gt', 'value' => 25], 'style' => 'bold'],
            ['column' => 'sub', 'when' => ['op' => 'eq', 'value' => 'acme annex'], 'style' => 'muted'],
        ],
        'sort' => [['column' => 'total']],
    ]);

    // Rows: 20 (HQ, line 2), 20 (annex, line 3; ties break by line id), 30, 100.
    expect($result->styles)->toBe([
        1 => ['sub' => ['muted']],
        2 => ['total' => ['bold']],
        3 => ['total' => ['positive', 'bold']],
    ])->and($result->toArray()['styles'])->toBeObject();
});

test('summary-only features are rejected in table mode', function (array $extra, string $path) {
    $user = seedSummaryData();

    try {
        F::run($user, ['dataset' => 'order_lines', 'columns' => [['field' => 'line.line_total', 'key' => 'total']], ...$extra]);
        $this->fail('Expected rejection.');
    } catch (InvalidReportDefinition $exception) {
        expect($exception->errors())->toHaveKey($path);
    }
})->with([
    'aggregate formula' => [['calculated' => [['key' => 'x', 'formula' => 'SUM(line.line_total)']]], 'calculated.0.formula'],
    'subtotals' => [['totals' => ['subtotals' => true]], 'totals.subtotals'],
    'pivot settings' => [['pivot' => ['rows' => [], 'column' => 'total', 'values' => []]], 'pivot'],
]);
