<?php

namespace App\Reporting\Sql;

use App\Reporting\DateBucket;

class MySqlDialect extends SqlDialect
{
    public function dateBucket(string $expression, DateBucket $bucket): string
    {
        return match ($bucket) {
            DateBucket::Day => "DATE_FORMAT({$expression}, '%Y-%m-%d')",
            DateBucket::Week => "DATE_FORMAT(DATE_SUB({$expression}, INTERVAL WEEKDAY({$expression}) DAY), '%Y-%m-%d')",
            DateBucket::Month => "DATE_FORMAT({$expression}, '%Y-%m')",
            DateBucket::Quarter => "CONCAT(YEAR({$expression}), '-Q', QUARTER({$expression}))",
            DateBucket::Year => "DATE_FORMAT({$expression}, '%Y')",
        };
    }

    public function dateDiffDays(string $end, string $start): string
    {
        return "DATEDIFF({$end}, {$start})";
    }

    public function numericPlaceholder(): string
    {
        return '?';
    }
}
