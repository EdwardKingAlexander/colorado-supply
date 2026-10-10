<?php

namespace App\Console\Commands;

use App\Services\PubLog\PubLogCatalog;
use Illuminate\Console\Command;
use Throwable;

class ActivatePubLog extends Command
{
    protected $signature = 'publog:activate {import : Validated import ID}
        {--expected-current= : Current active ID, or none for initial activation}
        {--rollback : Explicitly allow an older validated release}';

    protected $description = 'Atomically activate a validated release, retaining the prior snapshot for rollback';

    public function handle(PubLogCatalog $catalog): int
    {
        $expected = $this->option('expected-current');
        if (! ctype_digit((string) $this->argument('import')) || (int) $this->argument('import') < 1
            || ($expected !== 'none' && (! ctype_digit((string) $expected) || (int) $expected < 1))) {
            $this->error('Specify a valid import ID and --expected-current=<ID|none>.');

            return self::FAILURE;
        }
        try {
            $catalog->activate((int) $this->argument('import'), $expected === 'none' ? null : (int) $expected, (bool) $this->option('rollback'));
            $this->info('Catalog activated; all previous versions retained.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
