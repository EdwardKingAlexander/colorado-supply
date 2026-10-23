<?php

namespace App\Reporting\Processing;

use App\Reporting\Definition\PostProcessorSpec;

/**
 * Derived columns computed over the final, sorted rows. Percentages are
 * fractions (0.25 = 25%).
 */
final class PostProcessor
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<PostProcessorSpec>  $specs
     * @param  ?array<string, mixed>  $totals  the grand totals row, used as the % of total denominator
     * @return list<array<string, mixed>>
     */
    public static function apply(array $rows, array $specs, ?array $totals = null): array
    {
        foreach ($specs as $spec) {
            $values = array_map(fn (array $row) => self::number($row[$spec->column] ?? null), $rows);
            $derived = match ($spec->operation) {
                'percent_of_total' => self::percentOfTotal($values, self::number($totals[$spec->column] ?? null)),
                'running_total' => self::runningTotal($values),
                'change_vs_previous' => self::change($values, relative: false),
                'percent_change_vs_previous' => self::change($values, relative: true),
                'rank' => self::rank($values),
            };

            foreach ($rows as $index => $row) {
                $rows[$index][$spec->key] = $derived[$index];
            }
        }

        return $rows;
    }

    /**
     * The totals row's values for post-processed columns: 100% for % of
     * total, blank for the rest (a running total or rank of totals means nothing).
     *
     * @param  array<string, mixed>  $totals
     * @param  list<PostProcessorSpec>  $specs
     * @return array<string, mixed>
     */
    public static function totals(array $totals, array $specs): array
    {
        foreach ($specs as $spec) {
            $totals[$spec->key] = $spec->operation === 'percent_of_total' && self::number($totals[$spec->column] ?? null) ? 1.0 : null;
        }

        return $totals;
    }

    /**
     * @param  list<?float>  $values
     * @return list<?float>
     */
    private static function percentOfTotal(array $values, ?float $total): array
    {
        $total ??= array_sum(array_filter($values, fn (?float $value) => $value !== null));

        return array_map(fn (?float $value) => $value === null || $total == 0.0 ? null : round($value / $total, 6), $values);
    }

    /**
     * @param  list<?float>  $values
     * @return list<?float>
     */
    private static function runningTotal(array $values): array
    {
        $sum = 0.0;

        return array_map(function (?float $value) use (&$sum) {
            $sum += $value ?? 0.0;

            return round($sum, 6);
        }, $values);
    }

    /**
     * @param  list<?float>  $values
     * @return list<?float>
     */
    private static function change(array $values, bool $relative): array
    {
        $result = [];

        foreach ($values as $index => $value) {
            $previous = $values[$index - 1] ?? null;

            $result[] = match (true) {
                $index === 0, $value === null, $previous === null => null,
                ! $relative => round($value - $previous, 6),
                $previous == 0.0 => null,
                default => round(($value - $previous) / abs($previous), 6),
            };
        }

        return $result;
    }

    /**
     * Competition ranking, highest first: 1, 2, 2, 4.
     *
     * @param  list<?float>  $values
     * @return list<?int>
     */
    private static function rank(array $values): array
    {
        $present = array_values(array_filter($values, fn (?float $value) => $value !== null));
        rsort($present);

        return array_map(
            fn (?float $value) => $value === null ? null : array_search($value, $present, true) + 1,
            $values,
        );
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) || is_bool($value) ? (float) $value : null;
    }
}
