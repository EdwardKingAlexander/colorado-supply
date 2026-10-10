<?php

namespace App\Console\Commands;

use App\Models\PubLogImport;
use App\Services\PubLog\PubLogCatalog;
use App\Services\PubLog\PubLogImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportPubLog extends Command
{
    protected $signature = 'publog:import {directory? : Folder containing the three official ZIP files}
        {--release= : Official release date, YYYY-MM-DD}
        {--resume= : Resume a previously prepared import ID}
        {--max-seconds=0 : Yield between batches after this many seconds; 0 runs to completion}
        {--activate : Activate after validation if the baseline catalog is unchanged}';

    protected $description = 'Stage, validate and checkpoint a PUB LOG release without overwriting curated parts';

    public function handle(PubLogImporter $importer, PubLogCatalog $catalog): int
    {
        try {
            $resume = $this->option('resume');
            if (! ctype_digit((string) $this->option('max-seconds'))
                || ($resume !== null && (! ctype_digit((string) $resume) || (int) $resume < 1))) {
                $this->error('Resume and max-seconds must be valid non-negative numeric IDs/durations.');

                return self::FAILURE;
            }
            if ($resume !== null) {
                if ($this->argument('directory') || $this->option('release')) {
                    $this->error('Use either directory + release or --resume, not both.');

                    return self::FAILURE;
                }
                $import = PubLogImport::findOrFail((int) $resume);
            } else {
                if (! $this->argument('directory') || ! $this->option('release')) {
                    $this->error('Provide a directory and --release=YYYY-MM-DD.');

                    return self::FAILURE;
                }
                $import = $importer->prepare($this->argument('directory'), $this->option('release'));
            }
            $this->info("Import {$import->id}: {$import->status}");
            $lastReport = 0;
            $result = $importer->run($import, (int) $this->option('max-seconds'), function ($member, $progress, $status) use (&$lastReport) {
                if (microtime(true) - $lastReport >= 5 || $status !== 'importing' || ($progress['done'] ?? false)) {
                    $this->line($member.': '.$status.' '.json_encode($progress));
                    $lastReport = microtime(true);
                }
            });
            if ($result->status !== 'ready') {
                $this->info("Checkpoint saved. Resume with publog:import --resume={$result->id}");

                return self::SUCCESS;
            }
            if ($this->option('activate')) {
                $catalog->activate($result->id, $result->manifest['baseline_import_id']);
                $this->info("Activated catalog {$result->id}.");
            } else {
                $this->info("Catalog {$result->id} is ready. Review publog:status before explicit activation.");
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
