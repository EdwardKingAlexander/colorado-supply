<?php

use App\Jobs\DibbsDailyDigestJob;
use App\Jobs\DibbsDownloadJob;
use App\Jobs\FetchSamOpportunitiesJob;
use App\Support\SamSearchParameters;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Metadata only; the operator reviews/downloads/imports releases explicitly.
Schedule::command('publog:check --scheduled')
    ->dailyAt('04:30')->withoutOverlapping()->onOneServer();

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
                // NAICS (configured lists) OR PSC discovery: DLA groups + 4-char PSCs.
                'psc_search' => (new SamSearchParameters)->defaultPscSearch(),
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
    // The app runs on UTC; without the timezone this fired at 06:00 UTC (midnight Mountain).
})->dailyAt('06:00')->timezone('America/Denver')->name('fetch-sam-opportunities');

/*
|--------------------------------------------------------------------------
| DIBBS RFQ Download
|--------------------------------------------------------------------------
|
| DLA posts a day's RFQ files the following morning (the 2026-10-02 zip was
| stamped 10-03 00:15 ET), so this runs at 07:00 Mountain and catches up on any
| recent day not yet imported. See ai/modules/dibbs-rfq-tracking.
|
*/

Schedule::call(function () {
    try {
        DibbsDownloadJob::dispatch();

        Log::info('Scheduled DIBBS download queued', ['trigger' => 'scheduled_task']);
    } catch (Throwable $e) {
        Log::error('Scheduled DIBBS download failed to queue', [
            'trigger' => 'scheduled_task',
            'error' => $e->getMessage(),
            'exception_class' => get_class($e),
        ]);
    }
})->dailyAt('07:00')->timezone('America/Denver')->name('dibbs-download');

// Fallback for the morning digest, which normally goes out right after the
// download imports a new file. Sends at most once a day (DibbsDailyDigestJob).
Schedule::call(function () {
    try {
        DibbsDailyDigestJob::dispatch();
    } catch (Throwable $e) {
        Log::error('Scheduled DIBBS digest failed to queue', ['error' => $e->getMessage(), 'exception_class' => get_class($e)]);
    }
})->dailyAt('08:30')->timezone('America/Denver')->name('dibbs-digest');

// DIBBS health and retention (ai/modules/dibbs-rfq-tracking, Phase 7).
Schedule::command('dibbs:check-freshness')->weekdays()->dailyAt('10:00')->timezone('America/Denver')->name('dibbs-freshness');
Schedule::command('dibbs:prune')->weeklyOn(0, '03:00')->timezone('America/Denver')->name('dibbs-prune');

// Bell retention (ai/modules/will-call-notifications-complete, phase 2).
Schedule::command('notifications:prune')->dailyAt('03:30')->timezone('America/Denver')->name('notifications-prune');

// Will call pickup reminders (ai/modules/will-call-notifications-complete, phase 4).
Schedule::command('will-call:remind')->dailyAt('09:00')->timezone('America/Denver')->name('will-call-remind');
