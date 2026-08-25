<?php

use App\Jobs\FetchSamOpportunitiesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Business Hub Scheduled Tasks
|--------------------------------------------------------------------------
|
| Check for upcoming deadlines and expiring documents daily at 8 AM.
| Sends email notifications to the configured recipient.
|
*/

Schedule::command('business:check-deadlines')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Automated Backups
|--------------------------------------------------------------------------
|
| Clean up backups that fall outside the retention policy before creating
| today's, then verify what's on the configured disk(s) is healthy.
|
*/

Schedule::command('backup:clean')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:run')
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:monitor')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| SAM.gov Opportunity Fetch
|--------------------------------------------------------------------------
|
| Fetch federal contract opportunities daily at 6am Mountain Time.
|
| This previously lived in bootstrap/app.php and called
| FetchSamOpportunitiesTool::handle([...]) statically. handle() is an instance
| method typed handle(Request $request): Response, so every run threw
| "Non-static method ... cannot be called statically" — and because Error does
| not extend Exception, the surrounding catch never caught it. The nightly
| fetch failed silently from the day it was written.
|
| Dispatching the job reuses the exact path the Filament control panel already
| exercises, and inherits queue retries plus the job's timeout.
|
*/

Schedule::call(function () {
    try {
        FetchSamOpportunitiesJob::dispatch(
            params: [
                'days_back' => 7,
                'place' => 'CO',
                'limit' => 100,
                'notice_type' => [
                    'Presolicitation',
                    'Solicitation',
                    'Combined Synopsis/Solicitation',
                ],
            ],
            userId: null
        );

        Log::info('Scheduled SAM.gov fetch queued', ['trigger' => 'scheduled_task']);
    } catch (Throwable $e) {
        // Throwable, not Exception: an Error here must not escape unnoticed the
        // way the static-call bug did.
        Log::error('Scheduled SAM.gov fetch failed to queue', [
            'trigger' => 'scheduled_task',
            'error' => $e->getMessage(),
            'exception_class' => get_class($e),
        ]);
    }
})->dailyAt('06:00')->name('fetch-sam-opportunities');
