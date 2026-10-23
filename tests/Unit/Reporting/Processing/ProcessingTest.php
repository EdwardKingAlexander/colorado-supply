<?php

use App\Reporting\Definition\CalculatedField;
use App\Reporting\Definition\FormatRule;
use App\Reporting\Definition\PostProcessorSpec;
use App\Reporting\Definition\SortSpec;
use App\Reporting\Processing\ConditionalFormatter;
use App\Reporting\Processing\FormulaCalculator;
use App\Reporting\Processing\PostProcessor;
use App\Reporting\Processing\RowSorter;

test('post-processors derive columns in row order', function () {
    $rows = [['v' => 10], ['v' => 30], ['v' => null], ['v' => 30], ['v' => 30]];
    $specs = array_map(fn (string $op) => new PostProcessorSpec($op, 'v', $op), array_keys(PostProcessorSpec::OPERATIONS));

    $result = PostProcessor::apply($rows, $specs);

    expect(array_column($result, 'percent_of_total'))->toBe([0.1, 0.3, null, 0.3, 0.3])
        ->and(array_column($result, 'running_total'))->toBe([10.0, 40.0, 40.0, 70.0, 100.0])
        ->and(array_column($result, 'change_vs_previous'))->toBe([null, 20.0, null, null, 0.0])
        ->and(array_column($result, 'percent_change_vs_previous'))->toBe([null, 2.0, null, null, 0.0])
        ->and(array_column($result, 'rank'))->toBe([4, 1, null, 1, 1]);
});

test('percent of total prefers the grand total as denominator', function () {
    $result = PostProcessor::apply([['v' => 25]], [new PostProcessorSpec('share', 'v', 'percent_of_total')], ['v' => 100]);

    expect($result[0]['share'])->toBe(0.25)
        ->and(PostProcessor::totals(['v' => 100], [new PostProcessorSpec('share', 'v', 'percent_of_total')])['share'])->toBe(1.0)
        ->and(PostProcessor::totals(['v' => 0], [new PostProcessorSpec('share', 'v', 'percent_of_total')])['share'])->toBeNull();
});

test('percent change from zero is blank', function () {
    $result = PostProcessor::apply([['v' => 0], ['v' => 5]], [new PostProcessorSpec('g', 'v', 'percent_change_vs_previous')]);

    expect(array_column($result, 'g'))->toBe([null, null]);
});

test('formulas that depend on later formulas are evaluated in dependency order', function () {
    $calculator = new FormulaCalculator([
        new CalculatedField('c', 'C', 'b * 2'),
        new CalculatedField('b', 'B', 'a + 1'),
        new CalculatedField('money', 'Money', 'a / 3', 'currency'),
    ]);

    expect($calculator->apply(['a' => 1]))->toBe(['a' => 1, 'b' => 2.0, 'c' => 4.0, 'money' => 0.33]);
});

test('the row sorter matches SQL ordering', function () {
    $rows = [['n' => 2, 's' => 'b'], ['n' => null, 's' => 'B'], ['n' => 10, 's' => 'a10'], ['n' => 2, 's' => 'a2']];

    expect(array_column(RowSorter::sort($rows, [new SortSpec('n')]), 'n'))->toBe([null, 2, 2, 10])
        ->and(array_column(RowSorter::sort($rows, [new SortSpec('n', 'desc'), new SortSpec('s')]), 's'))->toBe(['a10', 'a2', 'b', 'B'])
        ->and(array_column(RowSorter::sort($rows, [new SortSpec('s')]), 's'))->toBe(['a2', 'a10', 'b', 'B'])
        ->and(RowSorter::sort($rows, []))->toBe($rows);
});

test('conditional formatting rules', function (FormatRule $rule, array $expected) {
    $rows = [['v' => -5], ['v' => 0], ['v' => 10], ['v' => null], ['v' => 20]];

    expect(array_keys(ConditionalFormatter::styles($rows, [$rule])))->toBe($expected);
})->with([
    'gt' => [new FormatRule('v', 'gt', 0, 'bold'), [2, 4]],
    'lte' => [new FormatRule('v', 'lte', 0, 'bold'), [0, 1]],
    'between' => [new FormatRule('v', 'between', [0, 10], 'bold'), [1, 2]],
    'negative' => [new FormatRule('v', 'negative', null, 'negative'), [0]],
    'blank' => [new FormatRule('v', 'is_null', null, 'muted'), [3]],
    'equal' => [new FormatRule('v', 'eq', '10', 'bold'), [2]],
    'not equal' => [new FormatRule('v', 'neq', 10, 'bold'), [0, 1, 4]],
    'top 2' => [new FormatRule('v', 'top_n', 2, 'positive'), [2, 4]],
    'bottom 1' => [new FormatRule('v', 'bottom_n', 1, 'warning'), [0]],
    'top more than rows' => [new FormatRule('v', 'top_n', 99, 'positive'), [0, 1, 2, 4]],
]);

test('several rules on one cell stack their styles once each', function () {
    $styles = ConditionalFormatter::styles([['v' => 5]], [
        new FormatRule('v', 'gt', 1, 'bold'),
        new FormatRule('v', 'gt', 2, 'bold'),
        new FormatRule('v', 'gt', 3, 'positive'),
    ]);

    expect($styles)->toBe([0 => ['v' => ['bold', 'positive']]]);
});
