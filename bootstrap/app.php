<?php

use App\Console\Commands\EnsureEdwardAdminUser;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->withoutMiddleware(VerifyCsrfToken::class)
                ->group(base_path('routes/ai.php'));
        },
    )
    ->withCommands([
        EnsureEdwardAdminUser::class,
    ])
    ->withSchedule(function ($schedule) {
        // Section H - Fetch SAM.gov opportunities daily at 6am Mountain Time
        $schedule->call(function () {
            try {
                // Section H - Use static handle() for programmatic access
                $result = \App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool::handle([
                    'days_back' => 7,
                    'place' => 'CO',
                    'limit' => 100,
                    'notice_type' => [
                        'Presolicitation',
                        'Solicitation',
                        'Combined Synopsis/Solicitation',
                    ],
                ]);

                // Section H - Log results with detailed metrics
                if ($result['success']) {
                    \Illuminate\Support\Facades\Log::info('Scheduled SAM.gov fetch completed successfully', [
                        'trigger' => 'scheduled_task',
                        'total_records' => $result['summary']['total_records'] ?? 0,
                        'total_after_dedup' => $result['summary']['total_after_dedup'] ?? 0,
                        'duplicates_removed' => $result['summary']['duplicates_removed'] ?? 0,
                        'cache_hit_rate' => $result['summary']['cache_hit_rate'] ?? '0%',
                        'duration_ms' => $result['performance']['total_duration_ms'] ?? null,
                    ]);
                } elseif ($result['partial_success'] ?? false) {
                    // Section H - Partial success: Some NAICS failed but we have results
                    \Illuminate\Support\Facades\Log::warning('Scheduled SAM.gov fetch had partial success', [
                        'trigger' => 'scheduled_task',
                        'total_after_dedup' => $result['summary']['total_after_dedup'] ?? 0,
                        'successful_naics' => $result['summary']['successful_naics_count'] ?? 0,
                        'failed_naics' => $result['summary']['failed_naics_count'] ?? 0,
                        'failed_naics_details' => $result['summary']['failed_naics'] ?? [],
                        'error' => $result['error'] ?? 'Some NAICS queries failed',
                    ]);

                    // Section H - TODO: Send Slack/email alert for partial failures
                    // Notification::route('slack', config('logging.channels.slack.url'))
                    //     ->notify(new SamOpportunitiesPartialFailure($result));
                } else {
                    // Section H - Complete failure: All NAICS failed
                    \Illuminate\Support\Facades\Log::error('Scheduled SAM.gov fetch failed completely', [
                        'trigger' => 'scheduled_task',
                        'error' => $result['error'] ?? 'Unknown error',
                        'failed_naics' => $result['summary']['failed_naics'] ?? [],
                    ]);

                    // Section H - TODO: Send Slack/email alert for complete failures
                    // Notification::route('slack', config('logging.channels.slack.url'))
                    //     ->notify(new SamOpportunitiesCompleteFailure($result));
                }
            } catch (\Exception $e) {
                // Section H - Catch unexpected exceptions in scheduled task
                \Illuminate\Support\Facades\Log::error('Scheduled SAM.gov fetch threw exception', [
                    'trigger' => 'scheduled_task',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                // Section H - TODO: Send Slack/email alert for exceptions
                // Notification::route('slack', config('logging.channels.slack.url'))
                //     ->notify(new SamOpportunitiesException($e));
            }
        })->dailyAt('06:00')->name('fetch-sam-opportunities');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Web middleware stack
        $middleware->web([
            HandleInertiaRequests::class,
            \App\Http\Middleware\ScopeToCompany::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'auth.web_or_admin' => \App\Http\Middleware\AuthenticateWebOrAdmin::class,
            'store.enabled' => \App\Http\Middleware\EnsureStoreEnabled::class,
            'scope.company' => \App\Http\Middleware\ScopeToCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
