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
}
