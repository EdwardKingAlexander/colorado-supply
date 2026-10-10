<?php

namespace App\Console\Commands;

use App\Models\PubLogImport;
use App\Services\PubLog\PubLogCatalog;
use Illuminate\Console\Command;

class PubLogStatus extends Command
{
    protected $signature = 'publog:status {--json : Machine-readable status including recent import progress}';

    protected $description = 'Show catalog age, source check status and recent import history';

    public function handle(PubLogCatalog $catalog): int
    {
        $status = $catalog->status();
        $history = PubLogImport::latest('id')->limit(10)->get(['id', 'release_date', 'status', 'progress', 'validation', 'error', 'completed_at', 'activated_at', 'pinned']);
        if ($this->option('json')) {
            $this->line(json_encode(['catalog' => $status, 'imports' => $history], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->table(['ID', 'Release', 'Status', 'Error'], $history->map(fn ($row) => [$row->id, $row->release_date->format('Y-m-d'), $row->status, $row->error])->all());
        }

        return self::SUCCESS;
    }
}
