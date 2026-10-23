<?php

use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Reporting\DatasetRegistry;
use App\Reporting\Definition\InvalidReportDefinition;
use App\Reporting\Fields\Field;
use App\Reporting\FieldType;
use Tests\Feature\Reporting\ReportingFixtures as F;

/**
 * Seeds two companies and a no-company user, each with orders (lines,
 * payments, shipments) and quotes (lines), and returns how many root rows
 * each user should see per dataset.
 *
 * @return array{a: User, b: User, solo: User, expected: array<string, array<string, int>>}
 */
function seedTenants(): array
{
    $a = F::tenant('Alpha');
    $b = F::tenant('Bravo');
    $solo = F::tenant('Solo', withCompany: false);

    $seed = function (array $tenant, int $orders) {
        for ($i = 0; $i < $orders; $i++) {
            $order = F::order($tenant['user'], 100 + $i);
            F::line($order, $tenant['location'], 10);
            F::line($order, $tenant['sublocation'], 20);
            F::payment($order);
            F::shipment($order);

            $quote = F::quote($tenant['user']);
            F::quoteLine($quote, $tenant['location'], 40);
        }
    };

    $seed($a, 2);
    $seed($b, 3);
    $seed($solo, 1);

    // A colleague's order is company data; Alpha's user placing an order for
    // no company is still theirs. Neither belongs to Bravo or Solo.
    $colleague = User::factory()->create(['company_id' => $a['company']->id]);
    F::order($colleague, 7);
    F::order($a['user'], 8, ['company_id' => null]);

    // A Bravo quote converted into an order that is wrongly attributed to
    // Alpha's quote must not leak into Alpha's conversion totals.
    $alphaQuote = F::quote($a['user'], 10);
    F::order($b['user'], 999, ['quote_id' => $alphaQuote->id]);

    return [
        'a' => $a['user'],
        'b' => $b['user'],
        'solo' => $solo['user'],
        'expected' => [
            'a' => ['orders' => 4, 'order_lines' => 4, 'payments' => 2, 'shipments' => 2, 'quotes' => 3, 'quote_lines' => 2],
            'b' => ['orders' => 4, 'order_lines' => 6, 'payments' => 3, 'shipments' => 3, 'quotes' => 3, 'quote_lines' => 3],
            'solo' => ['orders' => 1, 'order_lines' => 2, 'payments' => 1, 'shipments' => 1, 'quotes' => 1, 'quote_lines' => 1],
        ],
    ];
}

/**
 * A filter group that matches every row, so any leak would show up in the count.
 *
 * @return array<string, mixed>
 */
function matchEverything(Field $field): array
{
    $rules = $field->type === FieldType::Boolean
        ? [['field' => $field->key, 'op' => 'is_true'], ['field' => $field->key, 'op' => 'is_false']]
        : [['field' => $field->key, 'op' => 'is_null'], ['field' => $field->key, 'op' => 'not_null']];

    return ['logic' => 'or', 'rules' => $rules];
}

test('every field of every dataset, as a column and as a filter, stays inside the tenant', function () {
    $world = seedTenants();
    $checked = 0;

    foreach (app(DatasetRegistry::class)->all() as $key => $dataset) {
        foreach (['a', 'b', 'solo'] as $who) {
            $user = $world[$who];
            $expected = $world['expected'][$who][$key];

            foreach ($dataset->fields() as $field) {
                $column = $field->selectable ? $field->key : array_key_first(array_filter(
                    $dataset->fields(), fn (Field $candidate) => $candidate->selectable,
                ));

                $result = F::run($user, [
                    'dataset' => $key,
                    'columns' => [['field' => $column]],
                    'filters' => matchEverything($field),
                ]);

                expect($result->rowCount)->toBe($expected, "{$who} / {$key} / {$field->key}");
                $checked++;
            }
        }
    }

    expect($checked)->toBeGreaterThan(200);
});

test('a company user sees colleagues and their own company-less orders but nothing else', function () {
    $world = seedTenants();

    $totals = F::column(F::run($world['a'], [
        'dataset' => 'orders',
        'columns' => [['field' => 'order.grand_total', 'key' => 'total']],
        'sort' => [['column' => 'total']],
    ]), 'total');

    expect($totals)->toBe([7.0, 8.0, 100.0, 101.0]);
});

test('quote conversion totals ignore orders from another tenant', function () {
    $world = seedTenants();

    $result = F::run($world['a'], [
        'dataset' => 'quotes',
        'columns' => [['field' => 'quote.grand_total', 'key' => 'total'], ['field' => 'quote.ordered_total', 'key' => 'ordered'], ['field' => 'quote.has_order', 'key' => 'converted']],
        'filters' => ['rules' => [['field' => 'quote.grand_total', 'op' => 'eq', 'value' => 10]]],
    ]);

    expect($result->rows)->toBe([['total' => 10.0, 'ordered' => 0.0, 'converted' => false]]);
});

test('location filters naming another company\'s locations return nothing', function () {
    $world = seedTenants();
    $bravoLocation = Location::query()->where('name', 'Bravo HQ')->firstOrFail();

    $result = F::run($world['a'], [
        'dataset' => 'order_lines',
        'columns' => [['field' => 'line.line_total']],
        'filters' => ['rules' => [['field' => 'location.id', 'op' => 'in', 'value' => [$bravoLocation->id]]]],
    ]);

    expect($result->rowCount)->toBe(0);
});

test('hostile values are bound, never interpolated', function (string $op, mixed $value) {
    $world = seedTenants();

    $result = F::run($world['a'], [
        'dataset' => 'orders',
        'columns' => [['field' => 'order.order_number']],
        'filters' => ['rules' => [['field' => 'order.po_number', 'op' => $op, 'value' => $value]]],
    ]);

    expect($result->rowCount)->toBe(0);
})->with([
    'tautology' => ['eq', "' OR '1'='1"],
    'comment' => ['eq', "x'); --"],
    'stacked' => ['contains', "'; DELETE FROM orders; --"],
    'list' => ['in', ["' OR 1=1 --", '") OR ("1"="1']],
]);

test('names that are not on the whitelist are rejected', function (array $definition, string $path) {
    $world = seedTenants();

    try {
        F::run($world['a'], array_merge(['dataset' => 'orders', 'columns' => [['field' => 'order.order_number']]], $definition));
        $this->fail('Expected the definition to be rejected.');
    } catch (InvalidReportDefinition $exception) {
        expect($exception->errors())->toHaveKey($path);
    }

    expect(Order::withoutGlobalScopes()->count())->toBeGreaterThan(0);
})->with([
    'raw column' => [['columns' => [['field' => 'o.id']]], 'columns.0.field'],
    'sql in field' => [['columns' => [['field' => 'order.order_number; DROP TABLE orders']]], 'columns.0.field'],
    'hidden field' => [['columns' => [['field' => 'order.internal_notes']]], 'columns.0.field'],
    'other dataset field' => [['columns' => [['field' => 'payment.amount']]], 'columns.0.field'],
    'sql in key' => [['columns' => [['field' => 'order.order_number', 'key' => 'x from users --']]], 'columns.0.key'],
    'sql in operator' => [['filters' => ['rules' => [['field' => 'order.po_number', 'op' => '= 1 OR 1=1', 'value' => 'x']]]], 'filters.rules.0.op'],
    'sql in sort' => [['sort' => [['column' => 'c0; DROP TABLE orders']]], 'sort.0.column'],
    'sql in sort direction' => [['sort' => [['column' => 'order_order_number', 'dir' => 'desc; DROP TABLE orders']]], 'sort.0.dir'],
    'sql in bucket' => [['columns' => [['field' => 'order.created_at', 'bucket' => "month') --"]]], 'columns.0.bucket'],
    'unknown dataset' => [['dataset' => 'users'], 'dataset'],
    'filter-only field as column' => [['columns' => [['field' => 'location.id']]], 'columns.0.field'],
]);
