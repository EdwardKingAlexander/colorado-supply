<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/** Compatibility tombstone for already serialized scraper jobs. No new callers dispatch this job. */
class FetchNsnDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(protected string $nsn) {}

    public function handle(): void
    {
        // A legacy job has no actor or durable result recipient. Never silently mutate curated data.
        throw new RuntimeException('Queued scraper lookup has been retired. Search this NSN in the admin PUB LOG Lookup page; review and save a curated part explicitly.');
    }
}
