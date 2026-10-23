<?php

use App\Reporting\Aggregate;
use App\Reporting\DatasetRegistry;
use App\Reporting\DateBucket;
use App\Reporting\Definition\ColumnSpec;
use App\Reporting\Definition\FilterGroup;
use App\Reporting\Definition\InvalidReportDefinition;
use App\Reporting\Definition\ReportDefinition;
use App\Reporting\Definition\ReportDefinitionValidator;
use App\Reporting\Limits;
use App\Reporting\Operator;

function definitionValidator(): ReportDefinitionValidator
{
    return new ReportDefinitionValidator(new DatasetRegistry);
}

function definitionWith(array $overrides = []): array
{
    return array_merge(['dataset' => 'orders', 'columns' => [['field' => 'order.order_number']]], $overrides);
}

function filterOn(string $field, string $op, mixed $value = null): array
{
    return definitionWith(['filters' => ['rules' => [array_filter(['field' => $field, 'op' => $op, 'value' => $value], fn ($v) => $v !== null)]]]);
}

test('a valid definition becomes a typed definition', function () {
    $definition = definitionValidator()->validated([
        'dataset' => 'order_lines',
        'columns' => [
            ['field' => 'order.created_at', 'bucket' => 'month', 'label' => 'Month'],
            ['field' => 'line.line_total', 'key' => 'spend', 'format' => 'currency'],
        ],
        'filters' => ['logic' => 'and', 'rules' => [
            ['field' => 'order.created_at', 'op' => 'relative', 'value' => 'last_90_days'],
            ['logic' => 'or', 'rules' => [
                ['field' => 'order.status', 'op' => 'in', 'value' => ['confirmed']],
                ['field' => 'location.id', 'op' => 'in', 'value' => [3, '4']],
            ]],
        ]],
        'sort' => [['column' => 'spend', 'dir' => 'desc']],
        'limit' => 250,
    ]);

    expect($definition)->toBeInstanceOf(ReportDefinition::class)
        ->and($definition->columns[0]->key)->toBe('order_created_at_month')
        ->and($definition->columns[0]->bucket)->toBe(DateBucket::Month)
        ->and($definition->columns[1]->key)->toBe('spend')
        ->and($definition->filters)->toBeInstanceOf(FilterGroup::class)
        ->and($definition->filters->rules[1])->toBeInstanceOf(FilterGroup::class)
        ->and($definition->filters->flatRules())->toHaveCount(3)
        ->and($definition->filters->flatRules()[0]->operator)->toBe(Operator::Relative)
        ->and($definition->sort[0]->direction)->toBe('desc')
        ->and($definition->limit)->toBe(250);
});

test('definitions round-trip through toArray', function () {
    $raw = [
        'version' => 1,
        'dataset' => 'orders',
        'mode' => 'table',
        'columns' => [['key' => 'number', 'field' => 'order.order_number', 'label' => 'No.']],
        'filters' => ['logic' => 'or', 'rules' => [
            ['field' => 'order.po_number', 'op' => 'is_null'],
            ['field' => 'order.grand_total', 'op' => 'between', 'value' => [1, 2]],
        ]],
        'sort' => [['column' => 'number', 'dir' => 'asc']],
        'limit' => 10,
    ];

    expect(definitionValidator()->validated($raw)->toArray())->toBe($raw);
});

test('generated column keys are stable and de-duplicated', function () {
    expect(ColumnSpec::keysFor([
        ['field' => 'order.created_at', 'bucket' => 'month'],
        ['field' => 'order.created_at', 'bucket' => 'month'],
        ['field' => 'order.grand_total'],
        ['field' => 'order.grand_total', 'key' => 'total'],
    ]))->toBe(['order_created_at_month', 'order_created_at_month_2', 'order_grand_total', 'total']);
});

test('structural errors are reported by path', function (mixed $input, string $path) {
    $errors = definitionValidator()->validate($input);

    expect($errors)->toHaveKey($path);
})->with([
    'not an object' => ['nope', 'definition'],
    'missing dataset' => [['columns' => []], 'dataset'],
    'unknown dataset' => [definitionWith(['dataset' => 'users']), 'dataset'],
    'future version' => [definitionWith(['version' => 2]), 'version'],
    'unsupported mode' => [definitionWith(['mode' => 'chart']), 'mode'],
    'no columns' => [definitionWith(['columns' => []]), 'columns'],
    'columns as a map' => [definitionWith(['columns' => ['a' => ['field' => 'order.order_number']]]), 'columns'],
    'too many columns' => [definitionWith(['columns' => array_fill(0, Limits::MAX_COLUMNS + 1, ['field' => 'order.order_number'])]), 'columns'],
    'duplicate keys' => [definitionWith(['columns' => [['field' => 'order.order_number', 'key' => 'a'], ['field' => 'order.po_number', 'key' => 'a']]]), 'columns'],
    'bad key' => [definitionWith(['columns' => [['field' => 'order.order_number', 'key' => 'Bad Key']]]), 'columns.0.key'],
    'long label' => [definitionWith(['columns' => [['field' => 'order.order_number', 'label' => str_repeat('x', 121)]]]), 'columns.0.label'],
    'bucket on text' => [definitionWith(['columns' => [['field' => 'order.order_number', 'bucket' => 'month']]]), 'columns.0.bucket'],
    'unknown bucket' => [definitionWith(['columns' => [['field' => 'order.created_at', 'bucket' => 'decade']]]), 'columns.0.bucket'],
    'unknown format' => [definitionWith(['columns' => [['field' => 'order.order_number', 'format' => 'html']]]), 'columns.0.format'],
    'aggregate in table mode' => [definitionWith(['columns' => [['field' => 'order.grand_total', 'aggregate' => 'sum']]]), 'columns.0.aggregate'],
    'sort by unknown column' => [definitionWith(['sort' => [['column' => 'order.grand_total']]]), 'sort.0.column'],
    'too many sorts' => [definitionWith(['sort' => array_fill(0, Limits::MAX_SORTS + 1, ['column' => 'order_order_number'])]), 'sort'],
    'limit too high' => [definitionWith(['limit' => Limits::MAX_ROW_LIMIT + 1]), 'limit'],
    'limit as text' => [definitionWith(['limit' => '10']), 'limit'],
    'bad logic' => [definitionWith(['filters' => ['logic' => 'xor', 'rules' => []]]), 'filters.logic'],
    'rules as a map' => [definitionWith(['filters' => ['rules' => ['a' => []]]]), 'filters.rules'],
    'too deep' => [definitionWith(['filters' => ['rules' => [['rules' => [['rules' => [['rules' => []]]]]]]]]), 'filters.rules.0.rules.0.rules.0'],
]);

test('too many filter rules are rejected', function () {
    $rules = array_fill(0, Limits::MAX_FILTER_RULES + 1, ['field' => 'order.po_number', 'op' => 'is_null']);

    expect(definitionValidator()->validate(definitionWith(['filters' => ['rules' => $rules]])))->toHaveKey('filters');
});

test('filter values must suit the field and operator', function (array $definition, string $path) {
    expect(definitionValidator()->validate($definition))->toHaveKey($path);
})->with([
    'unknown field' => [filterOn('order.secret', 'eq', 'x'), 'filters.rules.0.field'],
    'operator not allowed for type' => [filterOn('order.po_number', 'gt', 'x'), 'filters.rules.0.op'],
    'contains on enum' => [filterOn('order.status', 'contains', 'conf'), 'filters.rules.0.op'],
    'unknown operator' => [filterOn('order.po_number', 'like', 'x'), 'filters.rules.0.op'],
    'enum value not an option' => [filterOn('order.status', 'eq', 'shipped'), 'filters.rules.0.value'],
    'enum list with a bad option' => [filterOn('order.status', 'in', ['confirmed', 'nope']), 'filters.rules.0.value'],
    'empty list' => [filterOn('order.status', 'in', []), 'filters.rules.0.value'],
    'too many list values' => [filterOn('order.po_number', 'in', array_map('strval', range(1, Limits::MAX_LIST_VALUES + 1))), 'filters.rules.0.value'],
    'number as text' => [filterOn('order.grand_total', 'gt', 'lots'), 'filters.rules.0.value'],
    'infinite number' => [filterOn('order.grand_total', 'gt', INF), 'filters.rules.0.value'],
    'between needs two' => [filterOn('order.grand_total', 'between', [1]), 'filters.rules.0.value'],
    'between out of order' => [filterOn('order.grand_total', 'between', [5, 1]), 'filters.rules.0.value'],
    'date out of order' => [filterOn('order.created_at', 'between', ['2026-02-01', '2026-01-01']), 'filters.rules.0.value'],
    'bad date' => [filterOn('order.created_at', 'gt', '2026-13-01'), 'filters.rules.0.value'],
    'us date format' => [filterOn('order.created_at', 'gt', '01/05/2026'), 'filters.rules.0.value'],
    'unknown relative preset' => [filterOn('order.created_at', 'relative', 'last_decade'), 'filters.rules.0.value'],
    'text too long' => [filterOn('order.po_number', 'eq', str_repeat('x', Limits::MAX_STRING_LENGTH + 1)), 'filters.rules.0.value'],
    'empty contains' => [filterOn('order.po_number', 'contains', ''), 'filters.rules.0.value'],
    'array as text' => [filterOn('order.po_number', 'eq', ['x']), 'filters.rules.0.value'],
    'bad location id' => [array_merge(filterOn('location.id', 'in', ['1 OR 1=1']), ['dataset' => 'order_lines']), 'filters.rules.0.value'],
]);

test('values are accepted in their allowed shapes', function (array $definition) {
    expect(definitionValidator()->validate($definition))->toBe([]);
})->with([
    'numeric string' => [filterOn('order.grand_total', 'gte', '10.50')],
    'datetime' => [filterOn('order.created_at', 'lt', '2026-01-05 13:30')],
    'boolean ignores value' => [array_merge(filterOn('product.berry_compliant', 'is_true'), ['dataset' => 'order_lines'])],
    'null check' => [filterOn('order.po_number', 'not_null')],
    'empty filter group' => [definitionWith(['filters' => ['logic' => 'and', 'rules' => []]])],
]);

test('validated() throws with the errors', function () {
    try {
        definitionValidator()->validated(definitionWith(['columns' => [['field' => 'order.cost']]]));
        $this->fail('Expected an exception.');
    } catch (InvalidReportDefinition $exception) {
        expect($exception->errors())->toBe(['columns.0.field' => 'Unknown field.']);
    }
});

function summaryWith(array $overrides = []): array
{
    return array_merge([
        'dataset' => 'order_lines',
        'mode' => 'summary',
        'columns' => [
            ['field' => 'location.parent_name', 'key' => 'location'],
            ['field' => 'line.line_total', 'aggregate' => 'sum', 'key' => 'spend'],
            ['field' => 'line.sku', 'aggregate' => 'count', 'key' => 'lines'],
        ],
    ], $overrides);
}

test('summary definitions with every section validate and round-trip', function () {
    $raw = summaryWith([
        'version' => 1,
        'calculated' => [['key' => 'avg_line', 'label' => 'Average line', 'formula' => 'spend / lines', 'format' => 'currency']],
        'post' => [['key' => 'share', 'column' => 'spend', 'op' => 'percent_of_total']],
        'sort' => [['column' => 'avg_line', 'dir' => 'desc']],
        'limit' => 100,
        'totals' => ['grand' => true, 'subtotals' => false],
        'formatting' => [['column' => 'share', 'when' => ['op' => 'gt', 'value' => 0.5], 'style' => 'positive']],
    ]);

    $definition = definitionValidator()->validated($raw);

    expect($definition->isGrouped())->toBeTrue()
        ->and($definition->dimensions())->toHaveCount(1)
        ->and($definition->columns[1]->aggregate)->toBe(Aggregate::Sum)
        ->and($definition->toArray())->toBe([
            'version' => 1,
            'dataset' => 'order_lines',
            'mode' => 'summary',
            'columns' => [
                ['key' => 'location', 'field' => 'location.parent_name'],
                ['key' => 'spend', 'field' => 'line.line_total', 'aggregate' => 'sum'],
                ['key' => 'lines', 'field' => 'line.sku', 'aggregate' => 'count'],
            ],
            'calculated' => $raw['calculated'],
            'post' => $raw['post'],
            'filters' => null,
            'sort' => $raw['sort'],
            'limit' => 100,
            'totals' => ['grand' => true, 'subtotals' => false],
            'formatting' => $raw['formatting'],
        ]);
});

test('summary, formula, post, totals and formatting errors are reported by path', function (array $definition, string $path) {
    expect(definitionValidator()->validate($definition))->toHaveKey($path);
})->with([
    'unknown aggregate' => [summaryWith(['columns' => [['field' => 'line.line_total', 'aggregate' => 'median']]]), 'columns.0.aggregate'],
    'sum of text' => [summaryWith(['columns' => [['field' => 'line.sku', 'aggregate' => 'sum']]]), 'columns.0.aggregate'],
    'avg of a date' => [summaryWith(['columns' => [['field' => 'order.created_at', 'aggregate' => 'avg']]]), 'columns.0.aggregate'],
    'bucket and aggregate' => [summaryWith(['columns' => [['field' => 'order.created_at', 'bucket' => 'month', 'aggregate' => 'max']]]), 'columns.0.bucket'],
    'group limit' => [summaryWith(['limit' => Limits::MAX_GROUPS + 1]), 'limit'],
    'calculated key clash' => [summaryWith(['calculated' => [['key' => 'spend', 'formula' => '1']]]), 'calculated.0.key'],
    'calculated bad key' => [summaryWith(['calculated' => [['key' => 'Bad', 'formula' => '1']]]), 'calculated.0.key'],
    'formula syntax' => [summaryWith(['calculated' => [['key' => 'x', 'formula' => 'spend +']]]), 'calculated.0.formula'],
    'formula unknown column' => [summaryWith(['calculated' => [['key' => 'x', 'formula' => 'nope * 2']]]), 'calculated.0.formula'],
    'formula unknown field' => [summaryWith(['calculated' => [['key' => 'x', 'formula' => 'SUM(order.cost)']]]), 'calculated.0.formula'],
    'formula bare field in summary' => [summaryWith(['calculated' => [['key' => 'x', 'formula' => 'line.quantity * 2']]]), 'calculated.0.formula'],
    'formula aggregate not allowed' => [summaryWith(['calculated' => [['key' => 'x', 'formula' => 'SUM(line.sku)']]]), 'calculated.0.formula'],
    'formula refers to itself' => [summaryWith(['calculated' => [['key' => 'x', 'formula' => 'x + 1']]]), 'calculated.0.formula'],
    'formula cycle' => [summaryWith(['calculated' => [['key' => 'x', 'formula' => 'y + 1'], ['key' => 'y', 'formula' => 'x + 1']]]), 'calculated.0.formula'],
    'formula uses post column' => [summaryWith(['post' => [['column' => 'spend', 'op' => 'rank', 'key' => 'r']], 'calculated' => [['key' => 'x', 'formula' => 'r + 1']]]), 'calculated.0.formula'],
    'post on text' => [summaryWith(['post' => [['column' => 'location', 'op' => 'rank']]]), 'post.0.column'],
    'post unknown op' => [summaryWith(['post' => [['column' => 'spend', 'op' => 'median']]]), 'post.0.op'],
    'post key clash' => [summaryWith(['post' => [['column' => 'spend', 'op' => 'rank', 'key' => 'lines']]]), 'post.0.key'],
    'sort by post column' => [summaryWith(['post' => [['column' => 'spend', 'op' => 'rank', 'key' => 'r']], 'sort' => [['column' => 'r']]]), 'sort.0.column'],
    'totals not booleans' => [summaryWith(['totals' => ['grand' => 'yes']]), 'totals'],
    'totals unknown key' => [summaryWith(['totals' => ['grand' => true, 'average' => true]]), 'totals'],
    'subtotals need two groups' => [summaryWith(['totals' => ['subtotals' => true]]), 'totals.subtotals'],
    'format unknown column' => [summaryWith(['formatting' => [['column' => 'nope', 'when' => ['op' => 'gt', 'value' => 1], 'style' => 'bold']]]), 'formatting.0.column'],
    'format unknown style' => [summaryWith(['formatting' => [['column' => 'spend', 'when' => ['op' => 'gt', 'value' => 1], 'style' => 'red']]]), 'formatting.0.style'],
    'format number op on text' => [summaryWith(['formatting' => [['column' => 'location', 'when' => ['op' => 'gt', 'value' => 1], 'style' => 'bold']]]), 'formatting.0.when.op'],
    'format bad value' => [summaryWith(['formatting' => [['column' => 'spend', 'when' => ['op' => 'gt', 'value' => 'big'], 'style' => 'bold']]]), 'formatting.0.when.value'],
    'format top n zero' => [summaryWith(['formatting' => [['column' => 'spend', 'when' => ['op' => 'top_n', 'value' => 0], 'style' => 'bold']]]), 'formatting.0.when.value'],
    'pivot without mode' => [summaryWith(['pivot' => ['rows' => [], 'column' => 'location', 'values' => ['spend']]]), 'pivot'],
    'pivot without settings' => [summaryWith(['mode' => 'pivot']), 'pivot'],
    'post in pivot' => [summaryWith(['mode' => 'pivot', 'post' => [['column' => 'spend', 'op' => 'rank']], 'pivot' => ['rows' => [], 'column' => 'location', 'values' => ['spend']]]), 'post'],
]);

test('a two-dimension summary may have subtotals', function () {
    $definition = summaryWith(['columns' => [
        ['field' => 'location.parent_name'],
        ['field' => 'location.sublocation_name'],
        ['field' => 'line.line_total', 'aggregate' => 'sum'],
    ], 'totals' => ['subtotals' => true]]);

    expect(definitionValidator()->validate($definition))->toBe([]);
});
