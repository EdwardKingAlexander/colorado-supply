<?php

namespace App\Reporting\Sql;

use App\Reporting\DateBucket;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Driver-specific SQL fragments. Production runs MySQL while local dev and
 * tests run SQLite, so nothing date- or string-shaped may be written inline.
 */
abstract class SqlDialect
{
    public static function for(ConnectionInterface $connection): self
    {
        return match ($connection->getDriverName()) {
            'mysql', 'mariadb' => new MySqlDialect,
            'sqlite' => new SqliteDialect,
            default => throw new RuntimeException("Reporting does not support the [{$connection->getDriverName()}] driver."),
        };
    }

    /**
     * A sortable string key for the bucket: 2026-01-15, 2025-12-29 (week
     * start), 2026-01, 2026-Q1, 2026.
     */
    abstract public function dateBucket(string $expression, DateBucket $bucket): string;

    /**
     * Whole calendar days from $start to $end (null when either is null).
     */
    abstract public function dateDiffDays(string $end, string $start): string;

    /**
     * A placeholder that compares numerically. PDO binds floats as strings,
     * and SQLite never coerces a string compared with a computed expression.
     */
    abstract public function numericPlaceholder(): string;

    /**
     * The expression as text, so values of any type compare as strings.
     */
    abstract public function castText(string $expression): string;
}
