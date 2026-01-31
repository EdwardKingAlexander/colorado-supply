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
