<?php

namespace App\Console\Commands;

use App\Services\PubLog\PubLogDownloader;
use Illuminate\Console\Command;
use Throwable;

class CheckPubLog extends Command
{
    protected $signature = 'publog:check {--scheduled : Respect the configured schedule switch}';

    protected $description = 'Check official PUB LOG archive metadata without importing or activating data';

    public function handle(PubLogDownloader $downloader): int
    {
        if ($this->option('scheduled') && ! config('nsn_lookup.check_enabled')) {
            return self::SUCCESS;
        }
        try {
            $this->line(json_encode($downloader->check(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
