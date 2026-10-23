<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Product;
use App\Reporting\Definition\InvalidReportDefinition;
use Carbon\CarbonImmutable;
use Tests\Feature\Reporting\ReportingFixtures as F;

function ordersReport(array $columns, array $extra = []): array
{
    return array_merge(['dataset' => 'orders', 'columns' => $columns], $extra);
}

test('table mode returns the chosen columns with labels and types', function () {
    ['user' => $user] = F::tenant('Acme');
    $order = F::order($user, 125.5, ['po_number' => 'PO-77']);

    $result = F::run($user, ordersReport([
        ['field' => 'order.order_number'],
        ['field' => 'order.po_number', 'label' => 'Purchase order'],
        ['field' => 'order.grand_total', 'format' => 'currency'],
        ['field' => 'order.status'],
    ]));

    expect($result->rowCount)->toBe(1)
        ->and($result->truncated)->toBeFalse()
        ->and(array_column($result->columns, 'key'))->toBe(['order_order_number', 'order_po_number', 'order_grand_total', 'order_status'])
        ->and($result->columns[1]['label'])->toBe('Purchase order')
        ->and($result->columns[2]['type'])->toBe('money')
        ->and($result->columns[3]['options'])->toHaveKey('confirmed')
        ->and($result->rows[0])->toBe([
            'order_order_number' => $order->order_number,
            'order_po_number' => 'PO-77',
            'order_grand_total' => 125.5,
            'order_status' => 'confirmed',
        ]);
});

test('sort, limit and the preview cap are applied and truncation is reported', function () {
    ['user' => $user] = F::tenant('Acme');
    foreach ([30, 10, 20] as $total) {
        F::order($user, $total);
    }

    $sorted = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'sort' => [['column' => 'total', 'dir' => 'desc']],
    ]));
    expect(F::column($sorted, 'total'))->toBe([30.0, 20.0, 10.0]);

    $limited = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'sort' => [['column' => 'total', 'dir' => 'asc']],
        'limit' => 2,
    ]));
    expect(F::column($limited, 'total'))->toBe([10.0, 20.0])
        ->and($limited->rowCount)->toBe(2)
        ->and($limited->truncated)->toBeFalse();

    $preview = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']]), maxRows: 1);
    expect($preview->rows)->toHaveCount(1)
        ->and($preview->rowCount)->toBe(3)
        ->and($preview->truncated)->toBeTrue();
});

test('enum and number filters combine with nested and/or groups', function () {
    ['user' => $user] = F::tenant('Acme');
    F::order($user, 50, ['status' => OrderStatus::Confirmed, 'payment_status' => PaymentStatus::Paid]);
    F::order($user, 500, ['status' => OrderStatus::Confirmed, 'payment_status' => PaymentStatus::Unpaid]);
    F::order($user, 900, ['status' => OrderStatus::Cancelled, 'payment_status' => PaymentStatus::Unpaid]);
    F::order($user, 5, ['status' => OrderStatus::Draft, 'payment_status' => PaymentStatus::Unpaid]);

    // confirmed AND (paid OR total >= 400)
    $result = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['logic' => 'and', 'rules' => [
            ['field' => 'order.status', 'op' => 'eq', 'value' => 'confirmed'],
            ['logic' => 'or', 'rules' => [
                ['field' => 'order.payment_status', 'op' => 'eq', 'value' => 'paid'],
                ['field' => 'order.grand_total', 'op' => 'gte', 'value' => 400],
            ]],
        ]],
        'sort' => [['column' => 'total', 'dir' => 'asc']],
    ]));

    expect(F::column($result, 'total'))->toBe([50.0, 500.0]);

    $notIn = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['rules' => [['field' => 'order.status', 'op' => 'not_in', 'value' => ['confirmed', 'draft']]]],
    ]));
    expect(F::column($notIn, 'total'))->toBe([900.0]);

    $between = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['rules' => [['field' => 'order.grand_total', 'op' => 'between', 'value' => [10, 600]]]],
        'sort' => [['column' => 'total']],
    ]));
    expect(F::column($between, 'total'))->toBe([50.0, 500.0]);
});

test('not equals keeps blank values', function () {
    ['user' => $user] = F::tenant('Acme');
    F::order($user, 1, ['po_number' => 'A']);
    F::order($user, 2, ['po_number' => 'B']);
    F::order($user, 3, ['po_number' => null]);

    $result = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['rules' => [['field' => 'order.po_number', 'op' => 'neq', 'value' => 'A']]],
        'sort' => [['column' => 'total']],
    ]));

    expect(F::column($result, 'total'))->toBe([2.0, 3.0]);
});

test('text filters treat LIKE wildcards literally', function () {
    ['user' => $user] = F::tenant('Acme');
    F::order($user, 1, ['po_number' => '100% done']);
    F::order($user, 2, ['po_number' => '1000 units']);
    F::order($user, 3, ['po_number' => 'JOB_1']);
    F::order($user, 4, ['po_number' => 'JOBX1']);

    $percent = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['rules' => [['field' => 'order.po_number', 'op' => 'contains', 'value' => '0%']]],
    ]));
    $underscore = F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['rules' => [['field' => 'order.po_number', 'op' => 'starts_with', 'value' => 'JOB_']]],
    ]));

    expect(F::column($percent, 'total'))->toBe([1.0])
        ->and(F::column($underscore, 'total'))->toBe([3.0]);
});

test('date filters treat a bare date as the whole day', function () {
    ['user' => $user] = F::tenant('Acme');
    F::order($user, 1, ['created_at' => '2026-01-04 23:59:00']);
    F::order($user, 2, ['created_at' => '2026-01-05 00:00:00']);
    F::order($user, 3, ['created_at' => '2026-01-05 23:30:00']);
    F::order($user, 4, ['created_at' => '2026-01-06 00:01:00']);

    $run = fn (string $op, mixed $value) => F::column(F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['rules' => [['field' => 'order.created_at', 'op' => $op, 'value' => $value]]],
        'sort' => [['column' => 'total']],
    ])), 'total');

    expect($run('eq', '2026-01-05'))->toBe([2.0, 3.0])
        ->and($run('gt', '2026-01-05'))->toBe([4.0])
        ->and($run('gte', '2026-01-05'))->toBe([2.0, 3.0, 4.0])
        ->and($run('lt', '2026-01-05'))->toBe([1.0])
        ->and($run('lte', '2026-01-05'))->toBe([1.0, 2.0, 3.0])
        ->and($run('between', ['2026-01-05', '2026-01-06']))->toBe([2.0, 3.0, 4.0])
        ->and($run('gte', '2026-01-05 12:00'))->toBe([3.0, 4.0]);
});

test('relative date filters use the current date', function () {
    CarbonImmutable::setTestNow('2026-03-15 10:00:00');
    ['user' => $user] = F::tenant('Acme');
    F::order($user, 1, ['created_at' => '2026-03-14 08:00:00']);
    F::order($user, 2, ['created_at' => '2026-02-20 08:00:00']);
    F::order($user, 3, ['created_at' => '2025-12-31 08:00:00']);

    $run = fn (string $preset) => F::column(F::run($user, ordersReport([['field' => 'order.grand_total', 'key' => 'total']], [
        'filters' => ['rules' => [['field' => 'order.created_at', 'op' => 'relative', 'value' => $preset]]],
        'sort' => [['column' => 'total']],
    ])), 'total');

    expect($run('yesterday'))->toBe([1.0])
        ->and($run('last_30_days'))->toBe([1.0, 2.0])
        ->and($run('last_month'))->toBe([2.0])
        ->and($run('year_to_date'))->toBe([1.0, 2.0])
        ->and($run('last_year'))->toBe([3.0])
        ->and($run('last_quarter'))->toBe([3.0]);

    CarbonImmutable::setTestNow();
});

test('date buckets produce sortable period keys on this driver', function () {
    ['user' => $user] = F::tenant('Acme');
    // Sunday 2026-01-04 belongs to the ISO week starting Monday 2025-12-29.
    F::order($user, 1, ['created_at' => '2026-01-04 18:00:00']);
    F::order($user, 2, ['created_at' => '2026-05-20 08:00:00']);

    $result = F::run($user, ordersReport([
        ['field' => 'order.grand_total', 'key' => 'total'],
        ['field' => 'order.created_at', 'bucket' => 'day', 'key' => 'day'],
        ['field' => 'order.created_at', 'bucket' => 'week', 'key' => 'week'],
        ['field' => 'order.created_at', 'bucket' => 'month', 'key' => 'month'],
        ['field' => 'order.created_at', 'bucket' => 'quarter', 'key' => 'quarter'],
        ['field' => 'order.created_at', 'bucket' => 'year', 'key' => 'year'],
    ], ['sort' => [['column' => 'total']]]));

    expect($result->rows[0])->toBe([
        'total' => 1.0, 'day' => '2026-01-04', 'week' => '2025-12-29', 'month' => '2026-01', 'quarter' => '2026-Q1', 'year' => '2026',
    ])->and($result->rows[1])->toBe([
        'total' => 2.0, 'day' => '2026-05-20', 'week' => '2026-05-18', 'month' => '2026-05', 'quarter' => '2026-Q2', 'year' => '2026',
    ])->and($result->columns[2]['type'])->toBe('string');
});

test('order lines expose location names and a location filter that includes sublocations', function () {
    ['user' => $user, 'location' => $hq, 'sublocation' => $annex] = F::tenant('Acme');
    $order = F::order($user, 60);
    F::line($order, $hq, 10);
    F::line($order, $annex, 20);
    F::line($order, null, 30);

    $columns = [
        ['field' => 'line.line_total', 'key' => 'total'],
        ['field' => 'location.parent_name', 'key' => 'location'],
        ['field' => 'location.sublocation_name', 'key' => 'sublocation'],
    ];
    $all = F::run($user, ['dataset' => 'order_lines', 'columns' => $columns, 'sort' => [['column' => 'total']]]);

    expect($all->rows)->toBe([
        ['total' => 10.0, 'location' => 'Acme HQ', 'sublocation' => 'Primary'],
        ['total' => 20.0, 'location' => 'Acme HQ', 'sublocation' => 'Acme Annex'],
        ['total' => 30.0, 'location' => 'Unassigned', 'sublocation' => 'Primary'],
    ]);

    $filter = fn (string $op, ?array $value = null) => F::column(F::run($user, [
        'dataset' => 'order_lines',
        'columns' => $columns,
        'filters' => ['rules' => [array_filter(['field' => 'location.id', 'op' => $op, 'value' => $value])]],
        'sort' => [['column' => 'total']],
    ]), 'total');

    expect($filter('in', [$hq->id]))->toBe([10.0, 20.0])
        ->and($filter('in', [$annex->id]))->toBe([20.0])
        ->and($filter('not_in', [$hq->id]))->toBe([30.0])
        ->and($filter('is_null'))->toBe([30.0]);
});

test('order lines join product attributes and order fields', function () {
    ['user' => $user, 'location' => $hq] = F::tenant('Acme');
    $category = Category::factory()->create(['name' => 'Fasteners']);
    $product = Product::factory()->create(['category_id' => $category->id, 'berry_compliant' => true, 'nsn' => '1234-00-111-2222']);
    $order = F::order($user, 40, ['po_number' => 'PO-1']);
    F::line($order, $hq, 20, 2, $product);
    F::line($order, $hq, 5);

    $result = F::run($user, [
        'dataset' => 'order_lines',
        'columns' => [
            ['field' => 'line.quantity', 'key' => 'qty'],
            ['field' => 'product.nsn', 'key' => 'nsn'],
            ['field' => 'product.category', 'key' => 'category'],
            ['field' => 'order.po_number', 'key' => 'po'],
        ],
        'filters' => ['rules' => [['field' => 'product.berry_compliant', 'op' => 'is_true']]],
    ]);

    expect($result->rows)->toHaveCount(1)
        ->and($result->rows[0]['qty'])->toEqual(2)
        ->and($result->rows[0]['nsn'])->toBe('1234-00-111-2222')
        ->and($result->rows[0]['category'])->toBe('Fasteners')
        ->and($result->rows[0]['po'])->toBe('PO-1');
});

test('shipments compute days to deliver', function () {
    ['user' => $user] = F::tenant('Acme');
    F::shipment(F::order($user), ['shipped_at' => '2026-01-16 22:00:00', 'delivered_at' => '2026-01-19 06:00:00']);
    F::shipment(F::order($user), ['status' => 'shipped', 'delivered_at' => null]);

    $result = F::run($user, [
        'dataset' => 'shipments',
        'columns' => [['field' => 'shipment.status', 'key' => 'status'], ['field' => 'shipment.days_to_deliver', 'key' => 'days']],
        'sort' => [['column' => 'status']],
    ]);

    expect($result->rows)->toBe([
        ['status' => 'delivered', 'days' => 3],
        ['status' => 'shipped', 'days' => null],
    ]);

    $slow = F::run($user, [
        'dataset' => 'shipments',
        'columns' => [['field' => 'shipment.days_to_deliver', 'key' => 'days']],
        'filters' => ['rules' => [['field' => 'shipment.days_to_deliver', 'op' => 'gte', 'value' => 2.5]]],
    ]);
    expect($slow->rowCount)->toBe(1);
});

test('quotes report product-page requests and conversion to orders', function () {
    ['user' => $user] = F::tenant('Acme');
    $converted = F::quote($user, 300, ['status' => 'ordered']);
    F::order($user, 280, ['quote_id' => $converted->id]);
    F::order($user, 20, ['quote_id' => $converted->id]);
    F::quote($user, 75, ['status' => 'draft', 'walk_in_label' => 'product-page-quote']);

    $result = F::run($user, [
        'dataset' => 'quotes',
        'columns' => [
            ['field' => 'quote.grand_total', 'key' => 'total'],
            ['field' => 'quote.is_product_request', 'key' => 'request'],
            ['field' => 'quote.has_order', 'key' => 'converted'],
            ['field' => 'quote.ordered_total', 'key' => 'ordered'],
        ],
        'sort' => [['column' => 'total']],
    ]);

    expect($result->rows)->toBe([
        ['total' => 75.0, 'request' => true, 'converted' => false, 'ordered' => 0.0],
        ['total' => 300.0, 'request' => false, 'converted' => true, 'ordered' => 300.0],
    ]);

    $requests = F::run($user, [
        'dataset' => 'quotes',
        'columns' => [['field' => 'quote.grand_total']],
        'filters' => ['rules' => [['field' => 'quote.ordered_total', 'op' => 'gt', 'value' => 299.5]]],
    ]);
    expect($requests->rowCount)->toBe(1);
});

test('payments and quote lines run with their parent fields', function () {
    ['user' => $user, 'sublocation' => $annex] = F::tenant('Acme');
    F::payment(F::order($user, 99, ['po_number' => 'PO-PAY']), ['amount' => 99]);
    F::quoteLine(F::quote($user, 40, ['quote_number' => 'Q-LINES']), $annex, 40, 4);

    $payments = F::run($user, ['dataset' => 'payments', 'columns' => [
        ['field' => 'payment.amount', 'key' => 'amount'], ['field' => 'payment.method', 'key' => 'method'], ['field' => 'order.po_number', 'key' => 'po'],
    ]]);
    $quoteLines = F::run($user, ['dataset' => 'quote_lines', 'columns' => [
        ['field' => 'line.quantity', 'key' => 'qty'], ['field' => 'location.sublocation_name', 'key' => 'sub'], ['field' => 'quote.quote_number', 'key' => 'quote'],
    ]]);

    expect($payments->rows)->toBe([['amount' => 99.0, 'method' => 'card', 'po' => 'PO-PAY']])
        ->and($quoteLines->rows[0]['sub'])->toBe('Acme Annex')
        ->and($quoteLines->rows[0]['quote'])->toBe('Q-LINES')
        ->and($quoteLines->rows[0]['qty'])->toEqual(4);
});

test('soft-deleted orders and quotes are excluded', function () {
    ['user' => $user] = F::tenant('Acme');
    F::order($user, 1);
    F::order($user, 2)->delete();
    F::quote($user)->delete();

    expect(F::run($user, ordersReport([['field' => 'order.grand_total']]))->rowCount)->toBe(1)
        ->and(F::run($user, ['dataset' => 'quotes', 'columns' => [['field' => 'quote.grand_total']]])->rowCount)->toBe(0);
});

test('invalid definitions are rejected before any query runs', function () {
    ['user' => $user] = F::tenant('Acme');

    F::run($user, ordersReport([['field' => 'order.internal_notes']]));
})->throws(InvalidReportDefinition::class);
