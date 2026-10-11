<?php

namespace App\Console\Commands;

use App\Models\PubLogImport;
use App\Services\PubLog\PubLogArchives;
use App\Services\PubLog\PubLogTables;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DiscardPubLogStaging extends Command
{
    protected $signature = 'publog:discard-staging {--force : Do not ask for confirmation}';

    protected $description = 'Drop a staged (not yet activated) release to free its disk space; the live catalog is untouched';

    public function handle(PubLogArchives $archives): int
    {
        $lock = Cache::lock('publog:writer', 3600);
        if (! $lock->get()) {
            $this->error('A PUB LOG import is running. Stop it first (it resumes from its checkpoint only while staged).');

            return self::FAILURE;
        }
        try {
            $staged = PubLogImport::whereNull('activated_at')->whereNotIn('status', ['abandoned', 'replaced'])->get()
                ->filter(fn (PubLogImport $import) => $import->manifest['staged'] ?? false);
            if ($staged->isEmpty() && ! PubLogTables::anyStagingExists()) {
                $this->info('Nothing is staged.');

                return self::SUCCESS;
            }
            if (! $this->option('force') && ! $this->confirm('Drop the staged release'.($staged->isNotEmpty() ? ' (import '.$staged->pluck('id')->implode(', ').')' : '').'? The live catalog is not affected.')) {
                return self::FAILURE;
            }
            PubLogTables::dropStaging();
            foreach ($staged as $import) {
                $import->update(['status' => 'abandoned', 'manifest' => [...$import->manifest, 'staged' => false]]);
                $archives->removeOwnedDirectory($import);
            }
            $this->info('Staged release discarded.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
