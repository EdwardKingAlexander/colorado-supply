<?php

namespace App\Reporting\Formula;

/**
 * Evaluates a parsed formula against one result row. Nulls propagate through
 * arithmetic, division by zero is null, and comparisons give 1 or 0.
 */
final class Evaluator
{
    /**
     * @param  array<string, mixed>  $row  column, calculated and hidden keys => values
     */
    public static function evaluate(Node $node, array $row): ?float
    {
        return match (true) {
            $node instanceof NumberNode => $node->value,
            $node instanceof ReferenceNode => self::number($row[$node->isField() ? self::rawKey($node->name) : $node->name] ?? null),
            $node instanceof AggregateNode => self::number($row[$node->key()] ?? null),
            $node instanceof UnaryNode => ($value = self::evaluate($node->operand, $row)) === null ? null : -$value,
            $node instanceof BinaryNode => self::binary($node, $row),
            $node instanceof CallNode => self::call($node, $row),
        };
    }

    /** The hidden key a raw field value is selected as in table reports. */
    public static function rawKey(string $field): string
    {
        return '__raw__'.str_replace('.', '__', $field);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function binary(BinaryNode $node, array $row): ?float
    {
        $left = self::evaluate($node->left, $row);
        $right = self::evaluate($node->right, $row);

        if ($left === null || $right === null) {
            return null;
        }

        return match ($node->operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right == 0.0 ? null : $left / $right,
            '<' => (float) ($left < $right),
            '<=' => (float) ($left <= $right),
            '>' => (float) ($left > $right),
            '>=' => (float) ($left >= $right),
            '=' => (float) (abs($left - $right) < 1e-9),
            '!=' => (float) (abs($left - $right) >= 1e-9),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function call(CallNode $node, array $row): ?float
    {
        // IF and COALESCE only evaluate what they need.
        if ($node->function === 'IF') {
            $condition = self::evaluate($node->arguments[0], $row);

            return self::evaluate($node->arguments[$condition !== null && $condition != 0.0 ? 1 : 2], $row);
        }

        if ($node->function === 'COALESCE') {
            foreach ($node->arguments as $argument) {
                if (($value = self::evaluate($argument, $row)) !== null) {
                    return $value;
                }
            }

            return null;
        }

        $values = array_map(fn (Node $argument) => self::evaluate($argument, $row), $node->arguments);

        if ($node->function === 'NULLIF') {
            return $values[0] !== null && $values[1] !== null && abs($values[0] - $values[1]) < 1e-9 ? null : $values[0];
        }

        if (in_array(null, $values, true)) {
            return null;
        }

        return match ($node->function) {
            'ROUND' => round($values[0], (int) ($values[1] ?? 0)),
            'ABS' => abs($values[0]),
            'MIN' => min($values),
            'MAX' => max($values),
        };
    }

    private static function number(mixed $value): ?float
    {
        return match (true) {
            is_bool($value) => (float) $value,
            is_int($value), is_float($value) => (float) $value,
            is_string($value) && is_numeric($value) => (float) $value,
            default => null,
        };
    }
}
