<?php

namespace App\Jobs;

use App\Models\ContractDocument;
use App\Services\DocumentParsing\DocumentParserManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    public function __construct(
        public ContractDocument $document
    ) {}

    public function handle(DocumentParserManager $parserManager): void
    {
        Log::info("ParseDocumentJob: Starting parse for document {$this->document->id}");

        // Update document status to processing
        $this->document->update([
            'status' => ContractDocument::STATUS_PROCESSING,
        ]);

        try {
            $result = $parserManager->parse($this->document);

            if ($result->success) {
                Log::info("ParseDocumentJob: Successfully parsed document {$this->document->id}", [
                    'driver' => $result->driverUsed,
                    'pages' => $result->getPageCount(),
                    'words' => $result->getWordCount(),
                ]);
            } else {
                Log::warning("ParseDocumentJob: Failed to parse document {$this->document->id}", [
                    'driver' => $result->driverUsed,
                    'error' => $result->errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("ParseDocumentJob: Exception while parsing document {$this->document->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->document->update([
                'status' => ContractDocument::STATUS_FAILED,
            ]);

            $this->document->logAction('parse_failed', [
                'error' => $e->getMessage(),
                'job_attempt' => $this->attempts(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ParseDocumentJob: Job failed permanently for document {$this->document->id}", [
            'error' => $exception->getMessage(),
        ]);

        $this->document->update([
            'status' => ContractDocument::STATUS_FAILED,
        ]);

        $this->document->logAction('parse_failed', [
            'error' => $exception->getMessage(),
            'permanent_failure' => true,
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'parse-document',
            'document:' . $this->document->id,
        ];
    }
}
