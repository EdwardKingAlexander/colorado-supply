<?php

namespace App\Jobs;

use App\Services\NsnLookupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchNsnDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $nsn;

    /**
     * Create a new job instance.
     */
    public function __construct(string $nsn)
    {
        $this->nsn = $nsn;
    }

    /**
     * Execute the job.
     */
    public function handle(NsnLookupService $nsnLookupService): void
    {
        Log::info("FetchNsnDataJob started for NSN: {$this->nsn}");

        try {
            $milSpecPart = $nsnLookupService->fetchAndPersistNsnData($this->nsn);

            if ($milSpecPart) {
                Log::info("FetchNsnDataJob completed successfully for NSN: {$this->nsn}. MilSpecPart ID: {$milSpecPart->id}");
            } else {
                Log::warning("FetchNsnDataJob completed but no MilSpecPart was returned for NSN: {$this->nsn}.");
            }
        } catch (\Exception $e) {
            Log::error("FetchNsnDataJob failed for NSN: {$this->nsn}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw the exception so Laravel's queue system can handle retries or failed jobs
            throw $e;
        }
    }
}
