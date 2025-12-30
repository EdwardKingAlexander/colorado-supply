<?php

namespace App\Jobs;

use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use App\Models\SamOpportunity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchSamOpportunitiesJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $params = [],
        public ?int $userId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('FetchSamOpportunitiesJob starting', [
                'job_id' => $this->job?->getJobId(),
                'user_id' => $this->userId,
                'params' => $this->params,
            ]);

            $tool = app(FetchSamOpportunitiesTool::class);
            $result = $tool->fetch($this->params);

            // Save opportunities to database
            $this->saveOpportunitiesToDatabase($result);

            // Persist result to shared state file for UI/exports
            $this->persistState($result);

            Log::info('FetchSamOpportunitiesJob completed', [
                'job_id' => $this->job?->getJobId(),
                'user_id' => $this->userId,
                'success' => $result['success'] ?? false,
                'partial_success' => $result['partial_success'] ?? false,
                'total_opportunities' => $result['summary']['total_after_dedup'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('FetchSamOpportunitiesJob failed', [
                'job_id' => $this->job?->getJobId(),
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Persist error state so UI can display it
            $this->persistErrorState($e->getMessage());

            throw $e;
        }
    }

    /**
     * Persist result to the shared state file for UI/export.
     */
    protected function persistState(array $data): void
    {
        $file = app_path('Mcp/Servers/Business/State/sam-opportunities.json');
        $dir = dirname($file);

        try {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::warning('Failed to persist SAM opportunities state in job', [
                'error' => $e->getMessage(),
                'file' => $file,
            ]);
        }
    }

    /**
     * Persist error state so UI can display failure messages.
     */
    protected function persistErrorState(string $errorMessage): void
    {
        $file = app_path('Mcp/Servers/Business/State/sam-opportunities.json');
        $dir = dirname($file);

        try {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $errorState = [
                'success' => false,
                'partial_success' => false,
                'fetched_at' => now()->toIso8601String(),
                'opportunities' => [],
                'error' => $errorMessage,
                'summary' => [
                    'total_records' => 0,
                    'total_after_dedup' => 0,
                    'duplicates_removed' => 0,
                    'returned' => 0,
                ],
            ];

            file_put_contents($file, json_encode($errorState, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::warning('Failed to persist SAM opportunities error state', [
                'error' => $e->getMessage(),
                'file' => $file,
            ]);
        }
    }

    /**
     * Save opportunities to database.
     */
    protected function saveOpportunitiesToDatabase(array $data): void
    {
        try {
            if (empty($data['opportunities'])) {
                Log::info('No opportunities to save to database');
                return;
            }

            $saved = 0;
            foreach ($data['opportunities'] as $opp) {
                SamOpportunity::updateOrCreate(
                    ['notice_id' => $opp['notice_id']],
                    [
                        'solicitation_number' => $opp['solicitation_number'] ?? null,
                        'title' => $opp['title'] ?? null,
                        'department' => $opp['agency_name'] ?? null,
                        'posted_date' => $opp['posted_date'] ?? null,
                        'response_deadline' => $opp['response_deadline'] ?? null,
                        'naics_code' => $opp['naics_code'] ?? null,
                        'classification_code' => $opp['psc_code'] ?? null,
                        'active' => true,
                        'set_aside' => $opp['set_aside_type'] ?? null,
                        'description' => $opp['description'] ?? null,
                        'type' => $opp['notice_type'] ?? null,
                        'links' => json_encode(['sam_url' => $opp['sam_url'] ?? null]),
                    ]
                );
                $saved++;
            }

            Log::info('Saved opportunities to database', [
                'count' => $saved,
                'total_in_result' => count($data['opportunities']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save opportunities to database', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
