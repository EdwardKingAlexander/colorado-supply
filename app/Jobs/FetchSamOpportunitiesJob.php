<?php

namespace App\Jobs;

use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use App\Models\SamOpportunity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
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
     *
     * Column names here must match the `sam_opportunities` schema. They did not
     * for a long time (`department`, `classification_code`, `active`, `type`,
     * and `links` do not exist), which made every insert throw. The throw was
     * swallowed and the job still reported success, so the table stayed empty
     * while the fetch looked healthy. Failures now propagate.
     *
     * @throws \Throwable when persistence fails
     */
    protected function saveOpportunitiesToDatabase(array $data): void
    {
        if (empty($data['opportunities'])) {
            Log::info('No opportunities to save to database');

            return;
        }

        $received = count($data['opportunities']);

        try {
            $saved = DB::transaction(function () use ($data) {
                $saved = 0;

                foreach ($data['opportunities'] as $opp) {
                    if (empty($opp['notice_id'])) {
                        Log::warning('Skipping SAM opportunity without a notice_id', [
                            'title' => $opp['title'] ?? 'Unknown',
                        ]);

                        continue;
                    }

                    SamOpportunity::updateOrCreate(
                        ['notice_id' => $opp['notice_id']],
                        [
                            'solicitation_number' => $opp['solicitation_number'] ?? null,
                            'title' => $opp['title'] ?? 'Untitled',
                            'agency' => $opp['agency_name'] ?? null,
                            'posted_date' => $opp['posted_date'] ?? null,
                            'response_deadline' => $opp['response_deadline'] ?? null,
                            'last_modified_date' => $opp['lastModifiedDate'] ?? null,
                            'naics_code' => $opp['naics_code'] ?? null,
                            'psc_code' => $opp['psc_code'] ?? null,
                            'set_aside' => $opp['set_aside_type'] ?? null,
                            'description' => $opp['description'] ?? null,
                            'notice_type' => $opp['notice_type'] ?? null,
                            'place_of_performance' => $opp['state_code'] ?? null,
                            'url' => $opp['sam_url'] ?? null,
                        ]
                    );

                    $saved++;
                }

                return $saved;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to save opportunities to database', [
                'error' => $e->getMessage(),
                'received' => $received,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        if ($saved !== $received) {
            Log::warning('Saved fewer opportunities than received', [
                'saved' => $saved,
                'received' => $received,
            ]);
        }

        Log::info('Saved opportunities to database', [
            'count' => $saved,
            'total_in_result' => $received,
        ]);
    }
}
