<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
