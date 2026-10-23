<?php

use App\Reporting\Aggregate;
use App\Reporting\Formula\AggregateNode;
use App\Reporting\Formula\BinaryNode;
use App\Reporting\Formula\Evaluator;
use App\Reporting\Formula\FormulaException;
use App\Reporting\Formula\Parser;

function calc(string $formula, array $row = []): ?float
{
    return Evaluator::evaluate(Parser::parse($formula), $row);
}

test('arithmetic follows normal precedence', function (string $formula, float $expected) {
    expect(calc($formula))->toBe($expected);
})->with([
    ['1 + 2 * 3', 7.0],
    ['(1 + 2) * 3', 9.0],
    ['10 - 4 - 3', 3.0],
    ['12 / 3 / 2', 2.0],
    ['-2 * -3', 6.0],
    ['- (1 + 1)', -2.0],
    ['.5 + 1.25', 1.75],
    ['2 * 3 > 5', 1.0],
    ['1 + 1 = 2', 1.0],
    ['3 <> 3', 0.0],
    ['4 <= 4', 1.0],
]);

test('functions', function (string $formula, ?float $expected) {
    expect(calc($formula))->toBe($expected);
})->with([
    ['ROUND(2.345, 2)', 2.35],
    ['round(2.5)', 3.0],
    ['ABS(-4)', 4.0],
    ['MIN(3, 1, 2)', 1.0],
    ['MAX(3, 1, 2)', 3.0],
    ['IF(1 > 2, 10, 20)', 20.0],
    ['IF(0, 1, 2)', 2.0],
    ['COALESCE(1 / 0, 5)', 5.0],
    ['NULLIF(3, 3)', null],
    ['NULLIF(3, 4)', 3.0],
]);

test('blank values and division by zero give blank results', function () {
    expect(calc('a + 1', ['a' => null]))->toBeNull()
        ->and(calc('a / b', ['a' => 1, 'b' => 0]))->toBeNull()
        ->and(calc('IF(a > 1, 1, 2)', ['a' => null]))->toBe(2.0)
        ->and(calc('ROUND(a)', ['a' => 'text']))->toBeNull();
});

test('references read column keys, raw fields and aggregates from the row', function () {
    $row = ['spend' => 10.0, 'qty' => 4, 'flag' => true, '__raw__line__quantity' => '3', '__sum__line__line_total' => 50.0];

    expect(calc('spend / qty', $row))->toBe(2.5)
        ->and(calc('flag + 1', $row))->toBe(2.0)
        ->and(calc('line.quantity * 2', $row))->toBe(6.0)
        ->and(calc('SUM(line.line_total) / 2', $row))->toBe(25.0)
        ->and(calc('missing', $row))->toBeNull();
});

test('one-field MIN and MAX are aggregates; several values are scalar', function () {
    expect(Parser::parse('MIN(line.unit_price)'))->toBeInstanceOf(AggregateNode::class)
        ->and(Parser::parse('MIN(line.unit_price)')->aggregate)->toBe(Aggregate::Min)
        ->and(Parser::parse('count_distinct(order.id)')->key())->toBe('__count_distinct__order__id')
        ->and(Parser::parse('MIN(a, 2)'))->not->toBeInstanceOf(AggregateNode::class)
        ->and(Parser::parse('SUM(a.b) + 1'))->toBeInstanceOf(BinaryNode::class);
});

test('syntax errors name what went wrong and where', function (string $formula, string $message, int $position) {
    try {
        Parser::parse($formula);
        $this->fail("Expected [{$formula}] to fail.");
    } catch (FormulaException $exception) {
        expect($exception->getMessage())->toContain($message)
            ->and($exception->position)->toBe($position);
    }
})->with([
    'empty' => ['  ', 'Enter a formula', 0],
    'bad character' => ['1 + $a', 'Unexpected character "$"', 4],
    'dangling operator' => ['1 +', 'ends too soon', 3],
    'unclosed' => ['(1 + 2', 'Missing closing parenthesis', 6],
    'extra' => ['1 2', 'Unexpected', 2],
    'unknown function' => ['EXEC(1)', 'Unknown function EXEC()', 0],
    'aggregate of expression' => ['SUM(1 + 2)', 'SUM() takes one field', 0],
    'aggregate of key' => ['AVG(spend)', 'AVG() takes one field', 0],
    'wrong arity' => ['IF(1, 2)', 'IF() takes 3 values', 0],
    'chained comparison' => ['1 < 2 < 3', 'Unexpected', 6],
    'missing comma' => ['ROUND(1 2)', 'Expected "," or ")"', 8],
]);

test('formulas are limited in length and size', function () {
    expect(fn () => Parser::parse(str_repeat('1+', 300).'1'))->toThrow(FormulaException::class, 'at most 500 characters')
        ->and(fn () => Parser::parse(str_repeat('1+', 120).'1'))->toThrow(FormulaException::class, 'too long');
});

test('random input never fails with anything but a formula error', function () {
    mt_srand(20261023);
    $alphabet = ['1', '0.5', 'a', 'b.c', 'SUM', 'IF', 'ROUND', 'MIN', '(', ')', ',', '+', '-', '*', '/', '<', '>=', '=', '!=', ' ', '$', ';', "'", 'DROP'];

    for ($i = 0; $i < 3000; $i++) {
        $formula = '';
        for ($j = 0, $length = mt_rand(1, 25); $j < $length; $j++) {
            $formula .= $alphabet[mt_rand(0, count($alphabet) - 1)];
        }

        try {
            $value = calc($formula, ['a' => 2, '__raw__b__c' => 3]);
            expect($value === null || is_float($value))->toBeTrue();
        } catch (FormulaException) {
            // expected for most random input
        }
    }
});
