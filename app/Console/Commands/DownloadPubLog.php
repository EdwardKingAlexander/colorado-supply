<?php

namespace App\Console\Commands;

use App\Services\PubLog\PubLogDownloader;
use Illuminate\Console\Command;
use Throwable;

class DownloadPubLog extends Command
{
    protected $signature = 'publog:download';

    protected $description = 'Download official PUB LOG ZIPs to private disk; does not import or activate them';

    public function handle(PubLogDownloader $downloader): int
    {
        try {
            $directory = $downloader->download();
            $this->info("Downloaded to {$directory}. Confirm the published release date, then run publog:import.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
