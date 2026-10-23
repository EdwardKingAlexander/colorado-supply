<?php

namespace App\Reporting\Processing;

use App\Reporting\Definition\SortSpec;

/**
 * Sorts result rows in PHP, for sorts SQL cannot do (calculated columns,
 * pivot rows). Mirrors SQL: blanks sort first ascending, last descending.
 */
final class RowSorter
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<SortSpec>  $sorts
     * @return list<array<string, mixed>>
     */
    public static function sort(array $rows, array $sorts): array
    {
        if ($sorts === []) {
            return $rows;
        }

        usort($rows, function (array $a, array $b) use ($sorts) {
            foreach ($sorts as $sort) {
                $result = self::compare($a[$sort->column] ?? null, $b[$sort->column] ?? null);

                if ($result !== 0) {
                    return $sort->direction === 'desc' ? -$result : $result;
                }
            }

            return 0;
        });

        return $rows;
    }

    public static function compare(mixed $a, mixed $b): int
    {
        return match (true) {
            $a === null && $b === null => 0,
            $a === null => -1,
            $b === null => 1,
            is_numeric($a) && is_numeric($b), is_bool($a) || is_bool($b) => (float) $a <=> (float) $b,
            default => strnatcasecmp((string) $a, (string) $b),
        };
    }
}
