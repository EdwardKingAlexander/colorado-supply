<?php

namespace App\Filament\Pages;

use App\Jobs\FetchSamOpportunitiesJob;
use App\Support\SamSearchParameters;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class FetchSamControlPanel extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'filament.pages.fetch-sam-control-panel';

    protected static UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $title = 'SAM Opportunities Control Panel';

    protected static ?int $navigationSort = 3;

    public ?array $formData = [];

    public ?array $lastResult = null;

    public bool $isPolling = false;

    public ?string $queueWorkerStatus = null;

    public array $queueLogTail = [];

    public function mount(): void
    {
        $params = new SamSearchParameters;

        $this->formData = [
            'naics_override' => $params->naics_codes_primary, // Default to primary only (9 codes for better performance)
            'psc_override' => [],
            'place' => null, // null = nationwide, or 2-letter state code
            'days_back' => 7,
            'notice_type' => $params->notice_types,
            'keywords' => '', // Leave empty for no keyword filtering
            'limit' => 1000, // Maximum allowed
            'clearCache' => true,
            'small_business_only' => false,
        ];

        $this->loadLastResult();
        $this->queueWorkerStatus = 'manual';
        $this->queueLogTail = $this->loadQueueLogTail();
    }

    /**
     * Check if the queue worker is running.
     */
    protected function checkQueueStatus(): void
    {
        try {
            $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')
                ->where('queue', 'default')
                ->count();

            if ($pendingJobs > 0) {
                Notification::make()
                    ->title('Queue Status')
                    ->body("There are {$pendingJobs} pending job(s) in the queue. Make sure the queue worker is running with 'php artisan queue:work'.")
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            // Silently fail - queue table might not exist
        }
    }

    public function form(Schema $schema): Schema
    {
        $params = new SamSearchParameters;

        // Build NAICS options with official descriptions
        $naicsPrimary = collect($params->naics_codes_primary)
            ->mapWithKeys(fn ($code) => [$code => $code.' - '.($params->naics_descriptions[$code] ?? 'Unknown')])
            ->toArray();

        $naicsSecondary = collect($params->naics_codes_secondary)
            ->mapWithKeys(fn ($code) => [$code => $code.' - '.($params->naics_descriptions[$code] ?? 'Unknown')])
            ->toArray();

        $naicsOptions = $naicsPrimary + $naicsSecondary;

        $pscOptions = collect($params->psc_codes)
            ->mapWithKeys(fn ($code) => [$code => $code])
            ->toArray();

        return $schema
            ->components([
                Section::make('Query Parameters')
                    ->schema([
                        Select::make('naics_override')
                            ->label('NAICS Codes')
                            ->options($naicsOptions)
                            ->multiple()
                            ->searchable()
                            ->default($params->naics_codes_primary)
                            ->helperText('⚡ Defaults to core industry codes for best performance. Using all codes takes 2+ minutes and may timeout.')
                            ->columnSpanFull(),

                        Select::make('psc_override')
                            ->label('PSC Codes (optional)')
                            ->options($pscOptions)
                            ->multiple()
                            ->searchable()
                            ->helperText('Leave empty to use default PSC codes')
                            ->columnSpanFull(),

                        Select::make('place')
                            ->label('State Filter')
                            ->options([
                                null => 'Nationwide (All States)',
                                'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                                'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                                'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                                'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                                'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                                'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                                'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                                'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                                'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                                'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                                'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                                'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                                'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
                            ])
                            ->default(null)
                            ->searchable()
                            ->helperText('Leave blank to search all states'),

                        TextInput::make('days_back')
                            ->label('Days Back')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->default(7)
                            ->required()
                            ->helperText('Number of days to look back'),

                        CheckboxList::make('notice_type')
                            ->label('Notice Types')
                            ->options(array_combine($params->notice_types, $params->notice_types))
                            ->default($params->notice_types)
                            ->columns(2)
                            ->columnSpanFull(),

                        TextInput::make('keywords')
                            ->label('Keywords (optional)')
                            ->placeholder('Leave blank to see all opportunities')
                            ->default('')
                            ->helperText('Enter comma-separated keywords to filter results (e.g., "supplies, equipment"). Leave blank to retrieve all matching opportunities.')
                            ->columnSpanFull(),

                        TextInput::make('limit')
                            ->label('Result Limit')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->default(1000)
                            ->required()
                            ->helperText('Maximum number of opportunities to return after deduplication'),

                        Toggle::make('clearCache')
                            ->label('Clear Cache')
                            ->helperText('Force fresh data from SAM.gov API')
                            ->default(true),

                        Toggle::make('small_business_only')
                            ->label('Small Business Set-Asides Only')
                            ->helperText('Filter to SBA set-aside opportunities')
                            ->default(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ])
            ->statePath('formData');
    }

    protected function getHeaderActions(): array
    {
        // Main action is rendered in the form area, not header
        return [];
    }

    public function executeFetch(): void
    {
        try {
            // Store timestamp when job was dispatched
            $timestamp = now()->timestamp;
            session(['sam_fetch_started_at' => $timestamp]);

            Log::info('SAM Control Panel - Dispatching fetch job', [
                'user_id' => auth()->id(),
                'timestamp' => $timestamp,
                'params' => $this->formData,
            ]);

            // Dispatch background job to avoid web server timeout
            FetchSamOpportunitiesJob::dispatch(
                params: $this->formData,
                userId: auth()->id()
            );

            // Start polling
            $this->isPolling = true;

            Log::info('SAM Control Panel - Job dispatched, polling started', [
                'user_id' => auth()->id(),
                'session_timestamp' => session('sam_fetch_started_at'),
            ]);

            Notification::make()
                ->title('Fetch Started')
                ->body('SAM.gov opportunities fetch has been queued. Results will appear automatically when ready. Check browser console for status updates.')
                ->info()
                ->persistent()
                ->send();

            // Dispatch browser event to start polling
            $this->dispatch('start-polling');
        } catch (\Exception $e) {
            Log::error('SAM.gov fetch job dispatch failed in control panel', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            Notification::make()
                ->title('Error')
                ->body('Failed to queue fetch: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Check if the fetch has completed.
     */
    public function checkFetchStatus(): bool
    {
        $this->loadLastResult();

        $startedAt = session('sam_fetch_started_at');

        if (! $startedAt) {
            Log::debug('checkFetchStatus: No session timestamp found');
            return false;
        }

        if (! $this->lastResult) {
            Log::debug('checkFetchStatus: No lastResult data', [
                'started_at' => $startedAt,
            ]);
            return false;
        }

        $fetchedAt = isset($this->lastResult['fetched_at'])
            ? \Carbon\Carbon::parse($this->lastResult['fetched_at'])->timestamp
            : 0;

        Log::debug('checkFetchStatus: Comparing timestamps', [
            'started_at' => $startedAt,
            'fetched_at' => $fetchedAt,
            'is_newer' => $fetchedAt > $startedAt,
        ]);

        // If the result was fetched after the job was started, it's complete
        if ($fetchedAt > $startedAt) {
            session()->forget('sam_fetch_started_at');
            $this->isPolling = false;

            // Check if there was an error
            $error = $this->lastResult['error'] ?? null;
            $success = $this->lastResult['success'] ?? false;
            $totalOpps = $this->lastResult['summary']['returned'] ?? ($this->lastResult['summary']['total_after_dedup'] ?? 0);

            Log::info('SAM fetch completed - new results detected', [
                'fetched_at' => $fetchedAt,
                'started_at' => $startedAt,
                'total_opportunities' => $totalOpps,
                'success' => $success,
            ]);

            if ($error) {
                Notification::make()
                    ->title('Fetch Failed')
                    ->body('Error: ' . $error)
                    ->danger()
                    ->persistent()
                    ->send();
            } else {
                Notification::make()
                    ->title($success ? 'Fetch Complete!' : 'Fetch Completed with Warnings')
                    ->body($success
                        ? "Successfully fetched {$totalOpps} opportunities from SAM.gov."
                        : 'The fetch completed but encountered some warnings. Check the results below.')
                    ->success($success)
                    ->warning(!$success)
                    ->duration(8000)
                    ->send();
            }

            $this->dispatch('stop-polling');

            return true;
        }

        return false;
    }

    /**
     * Persist the last result so it can be reloaded on page refresh.
     */
    protected function persistLastResult(array $result): void
    {
        $file = app_path('Mcp/Servers/Business/State/sam-opportunities.json');

        try {
            $dir = dirname($file);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($file, json_encode($result, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::warning('Failed to persist SAM.gov control panel result', [
                'error' => $e->getMessage(),
                'file' => $file,
            ]);
        }
    }

    protected function loadLastResult(): void
    {
        $file = app_path('Mcp/Servers/Business/State/sam-opportunities.json');

        if (file_exists($file)) {
            $this->lastResult = json_decode(file_get_contents($file), true);

            // Normalize legacy summaries so the UI numbers match what is displayed
            if (isset($this->lastResult['summary'])) {
                $summary =& $this->lastResult['summary'];
                $opportunities = $this->lastResult['opportunities'] ?? [];
                $returned = $summary['returned'] ?? count($opportunities);
                $dedup = $summary['total_after_dedup'] ?? $returned;

                // If legacy data reports more results than we actually have, adjust and note the filtered delta.
                if (($dedup > $returned) && ! isset($summary['filtered_out'])) {
                    $summary['dedup_before_filters'] = $dedup;
                    $summary['filtered_out'] = $dedup - $returned;
                    $summary['total_after_dedup'] = $returned;
                }

                $summary['returned'] = $returned;
                $summary['dedup_before_filters'] = $summary['dedup_before_filters'] ?? $dedup;
                $summary['filtered_out'] = $summary['filtered_out'] ?? 0;
            }
        }
    }

    public function getLastResult(): ?array
    {
        return $this->lastResult;
    }

    /**
     * Update queue worker status.
     */
    protected function updateQueueWorkerStatus(): void
    {
        // Manual mode: rely on user-managed worker
        $this->queueWorkerStatus = 'manual';
    }

    /**
     * Check if queue worker is running.
     */
    protected function isQueueWorkerRunning(): bool
    {
        // Check if there's a PID file
        $pidFile = storage_path('logs/queue-worker.pid');

        if (! file_exists($pidFile)) {
            return false;
        }

        $pid = (int) file_get_contents($pidFile);

        if (! $pid) {
            return false;
        }

        // Check if process is still running and actually a queue:work process
        if (PHP_OS_FAMILY === 'Windows') {
            $psInner = sprintf(
                '$p = Get-CimInstance Win32_Process -Filter \'ProcessId=%d\'; if ($p -and $p.CommandLine -like \'*queue:work*\') { $true } else { $false }',
                $pid
            );
            $cmd = 'powershell -NoProfile -Command "' . $psInner . '"';

            $result = trim((string) shell_exec($cmd));

            return $result === 'True';
        }

        // Linux/Mac
        return posix_kill($pid, 0);
    }

    /**
     * Load tail of the queue worker log for display.
     */
    protected function loadQueueLogTail(int $lines = 30): array
    {
        $logFile = storage_path('logs/queue-worker.log');

        if (! file_exists($logFile)) {
            return [];
        }

        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $start = max($lastLine - $lines, 0);
        $output = [];
        for ($i = $start; $i <= $lastLine; $i++) {
            $file->seek($i);
            $output[] = rtrim($file->current());
        }

        return $output;
    }

    /**
     * Stop the queue worker.
     */
    protected function stopQueueWorker(): void
    {
        try {
            $pidFile = storage_path('logs/queue-worker.pid');

            if (! file_exists($pidFile)) {
                Notification::make()
                    ->title('Not Running')
                    ->body('No queue worker PID file found.')
                    ->warning()
                    ->send();

                return;
            }

            $pid = (int) file_get_contents($pidFile);

            if (! $pid) {
                unlink($pidFile);

                Notification::make()
                    ->title('Invalid PID')
                    ->body('Queue worker PID file was invalid and has been removed.')
                    ->warning()
                    ->send();

                return;
            }

            // Kill the process
            if (PHP_OS_FAMILY === 'Windows') {
                exec("taskkill /F /PID {$pid} 2>NUL", $output, $returnCode);

                if ($returnCode === 0) {
                    unlink($pidFile);
                    $this->updateQueueWorkerStatus();

                    Notification::make()
                        ->title('Queue Worker Stopped')
                        ->body('The queue worker has been stopped successfully.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to stop queue worker. It may have already stopped.')
                        ->warning()
                        ->send();
                }
            } else {
                // Linux/Mac
                if (posix_kill($pid, SIGTERM)) {
                    unlink($pidFile);
                    $this->updateQueueWorkerStatus();

                    Notification::make()
                        ->title('Queue Worker Stopped')
                        ->body('The queue worker has been stopped successfully.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to stop queue worker. It may have already stopped.')
                        ->warning()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to stop queue worker', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error')
                ->body('Failed to stop queue worker: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
