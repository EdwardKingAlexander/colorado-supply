<?php

namespace App\Console\Commands;

use App\Models\PubLogImport;
use App\Services\PubLog\PubLogCatalog;
use App\Services\PubLog\PubLogDb;
use App\Services\PubLog\PubLogTables;
use Illuminate\Console\Command;
use Throwable;

class PubLogStatus extends Command
{
    protected $signature = 'publog:status
        {--json : Machine-readable status including recent import progress}
        {--sizes : Also report table sizes (MySQL only; can be slow on a busy server)}';

    protected $description = 'Show catalog age, staging, storage settings and recent import history';

    public function handle(PubLogCatalog $catalog): int
    {
        $connection = PubLogDb::connection();
        $staged = PubLogImport::whereNull('activated_at')->whereNotIn('status', ['abandoned', 'replaced'])->latest('id')->get()
            ->first(fn (PubLogImport $import) => $import->manifest['staged'] ?? false);
        $status = [
            ...$catalog->status(),
            'connection' => $connection->getName(),
            'history_imported' => (bool) config('nsn_lookup.import_relationships'),
            'staged_import_id' => $staged?->id,
            'staging_tables' => PubLogTables::anyStagingExists(),
        ];
        if ($connection->getDriverName() === 'mysql') {
            try {
                $binlog = $connection->selectOne('SELECT @@log_bin AS log_bin, @@binlog_expire_logs_seconds AS expire_seconds');
                $status['binlog'] = ['enabled' => (bool) $binlog->log_bin, 'expire_days' => round($binlog->expire_seconds / 86400, 1)];
            } catch (Throwable) {
                $status['binlog'] = 'unknown';
            }
            if ($this->option('sizes')) {
                // information_schema caches table statistics for up to 24h; ask for current ones.
                $connection->statement('SET SESSION information_schema_stats_expiry = 0');
                $tables = [...PubLogTables::DATA, ...array_map(PubLogTables::staging(...), PubLogTables::DATA)];
                $status['sizes_gb'] = collect($connection->select(
                    'SELECT TABLE_NAME AS name, DATA_LENGTH + INDEX_LENGTH AS bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('.implode(',', array_fill(0, count($tables), '?')).')',
                    $tables,
                ))->mapWithKeys(fn ($row) => [$row->name => round($row->bytes / 1e9, 2)])->all();
            }
        }
        $history = PubLogImport::latest('id')->limit(10)->get(['id', 'release_date', 'status', 'progress', 'validation', 'error', 'completed_at', 'activated_at']);
        if ($this->option('json')) {
            $this->line(json_encode(['catalog' => $status, 'imports' => $history], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->table(['ID', 'Release', 'Status', 'Error'], $history->map(fn ($row) => [$row->id, $row->release_date->format('Y-m-d'), $row->status, $row->error])->all());
        }

        return self::SUCCESS;
    }
}
