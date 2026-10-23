<?php

namespace App\Reporting\Processing;

use App\Reporting\Definition\FormatRule;

/**
 * Evaluates conditional formatting on the server so every renderer (UI,
 * XLSX, PDF) shows the same thing.
 */
final class ConditionalFormatter
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<FormatRule>  $rules
     * @param  array<string, list<string>>  $targets  rule column => result column keys it styles (pivot values fan out)
     * @return array<int, array<string, list<string>>> row index => column key => styles (rows without styles omitted)
     */
    public static function styles(array $rows, array $rules, array $targets = []): array
    {
        $styles = [];

        foreach ($rules as $rule) {
            foreach ($targets[$rule->column] ?? [$rule->column] as $column) {
                $values = array_column($rows, $column);
                $threshold = self::threshold($rule, $values);

                foreach ($rows as $index => $row) {
                    if (array_key_exists($column, $row) && self::matches($rule, $row[$column], $threshold)) {
                        $cell = $styles[$index][$column] ?? [];

                        if (! in_array($rule->style, $cell, true)) {
                            $styles[$index][$column] = [...$cell, $rule->style];
                        }
                    }
                }
            }
        }

        ksort($styles);

        return $styles;
    }

    /**
     * The cut-off value for top/bottom N rules.
     *
     * @param  list<mixed>  $values
     */
    private static function threshold(FormatRule $rule, array $values): ?float
    {
        if (! in_array($rule->operator, ['top_n', 'bottom_n'], true)) {
            return null;
        }

        $numbers = array_map('floatval', array_values(array_filter($values, 'is_numeric')));
        $rule->operator === 'top_n' ? rsort($numbers) : sort($numbers);

        return $numbers === [] ? null : $numbers[min($rule->value, count($numbers)) - 1];
    }

    private static function matches(FormatRule $rule, mixed $value, ?float $threshold): bool
    {
        if ($rule->operator === 'is_null') {
            return $value === null;
        }

        if ($value === null) {
            return false;
        }

        if (in_array($rule->operator, ['eq', 'neq'], true)) {
            $equal = is_numeric($value) && is_numeric($rule->value)
                ? abs((float) $value - (float) $rule->value) < 1e-9
                : strcasecmp((string) $value, (string) $rule->value) === 0;

            return $rule->operator === 'eq' ? $equal : ! $equal;
        }

        if (! is_numeric($value)) {
            return false;
        }

        $number = (float) $value;

        return match ($rule->operator) {
            'gt' => $number > (float) $rule->value,
            'gte' => $number >= (float) $rule->value,
            'lt' => $number < (float) $rule->value,
            'lte' => $number <= (float) $rule->value,
            'between' => $number >= (float) $rule->value[0] && $number <= (float) $rule->value[1],
            'negative' => $number < 0,
            'top_n' => $threshold !== null && $number >= $threshold,
            'bottom_n' => $threshold !== null && $number <= $threshold,
        };
    }
}
