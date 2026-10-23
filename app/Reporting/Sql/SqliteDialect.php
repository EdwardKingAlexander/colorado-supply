<?php

namespace App\Reporting\Sql;

use App\Reporting\DateBucket;

class SqliteDialect extends SqlDialect
{
    public function dateBucket(string $expression, DateBucket $bucket): string
    {
        return match ($bucket) {
            DateBucket::Day => "date({$expression})",
            // 'weekday 0' moves forward to Sunday (or stays on one), so six
            // days back is the Monday that starts the ISO week.
            DateBucket::Week => "date({$expression}, 'weekday 0', '-6 days')",
            DateBucket::Month => "strftime('%Y-%m', {$expression})",
            DateBucket::Quarter => "(strftime('%Y', {$expression}) || '-Q' || ((CAST(strftime('%m', {$expression}) AS INTEGER) + 2) / 3))",
            DateBucket::Year => "strftime('%Y', {$expression})",
        };
    }

    public function dateDiffDays(string $end, string $start): string
    {
        return "CAST(julianday(date({$end})) - julianday(date({$start})) AS INTEGER)";
    }

    public function numericPlaceholder(): string
    {
        return '(? + 0)';
    }

    public function castText(string $expression): string
    {
        return "CAST({$expression} AS TEXT)";
    }
}
