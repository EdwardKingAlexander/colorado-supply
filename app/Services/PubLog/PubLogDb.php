<?php

namespace App\Services\PubLog;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * The PUB LOG catalog can live in its own database (nsn_lookup.connection),
 * e.g. MySQL on a dedicated volume while the app uses another database.
 * Null means the default connection. Catalog tables are never joined to app
 * tables, so a separate connection is safe.
 */
class PubLogDb
{
    public static function name(): ?string
    {
        return config('nsn_lookup.connection') ?: null;
    }

    public static function connection(): Connection
    {
        return DB::connection(self::name());
    }

    /**
     * Bulk catalog writes can skip MySQL's binary log (PUBLOG_SKIP_BINLOG): the
     * catalog is always re-importable, and a 14 GB import would otherwise write
     * ~14 GB of binlog kept for 30 days. Affects only this session.
     */
    public static function prepareForBulkWrite(): void
    {
        $connection = self::connection();
        if (! config('nsn_lookup.skip_binlog') || $connection->getDriverName() !== 'mysql') {
            return;
        }
        try {
            $connection->statement('SET SESSION sql_log_bin = 0');
        } catch (\Throwable $e) {
            throw new \RuntimeException('PUBLOG_SKIP_BINLOG is set but MySQL refused to skip the binary log for this session (the database user needs SESSION_VARIABLES_ADMIN). Grant it or unset PUBLOG_SKIP_BINLOG.', 0, $e);
        }
    }
}
