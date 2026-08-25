<?php

declare(strict_types=1);

use App\Jobs\FetchSamOpportunitiesJob;
use App\Notifications\SamFetchFailedNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Fetch failure alerting
|--------------------------------------------------------------------------
|
| The fetch broke on 2026-07-09 and nobody knew until 2026-08-24, because the
| job caught its own errors and still logged "completed". These tests cover the
| signal that was missing — and, just as importantly, that it stays quiet for
| outcomes which are merely empty rather than broken.
|
*/

beforeEach(function () {
    Config::set('services.sam.api_key', 'test-api-key');
    Config::set('services.sam.alert_email', 'ops@example.test');
    Config::set('services.sam.base_url', 'https://sam.test/opportunities/v2/search');
});

function runFetchJob(): void
{
    (new FetchSamOpportunitiesJob(
        params: ['naics_override' => ['423840'], 'days_back' => 7, 'limit' => 100, 'clearCache' => true],
    ))->handle();
}

it('alerts when the endpoint is unreachable', function () {
    Notification::fake();
    Http::fake(['*' => Http::response('', 404)]);

    runFetchJob();

    Notification::assertSentOnDemand(
        SamFetchFailedNotification::class,
        function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'ops@example.test'
                && str_contains($notification->reason, 'sam:diagnose');
        }
    );
});

it('alerts when every NAICS query errors', function () {
    Notification::fake();
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    runFetchJob();

    Notification::assertSentOnDemand(SamFetchFailedNotification::class);
});

it('stays silent when the fetch succeeds with zero opportunities', function () {
    Notification::fake();

    // A narrow query legitimately matching nothing must not page anyone —
    // otherwise the alert becomes noise and gets ignored.
    Http::fake(['*' => Http::response(['totalRecords' => 0, 'opportunitiesData' => []], 200)]);

    runFetchJob();

    Notification::assertNothingSent();
});

it('stays silent on a normal successful fetch', function () {
    Notification::fake();

    Http::fake(['*' => Http::response([
        'totalRecords' => 1,
        'opportunitiesData' => [['noticeId' => 'n-1', 'title' => 'Test']],
    ], 200)]);

    runFetchJob();

    Notification::assertNothingSent();
});

it('does not crash the job when no alert recipient is configured', function () {
    Notification::fake();
    Config::set('services.sam.alert_email', null);
    Http::fake(['*' => Http::response('', 404)]);

    // A missing recipient must degrade to a log line, never take down the job
    // and lose the run's state file with it.
    runFetchJob();

    Notification::assertNothingSent();

    $state = json_decode(
        file_get_contents(app_path('Mcp/Servers/Business/State/sam-opportunities.json')),
        true
    );

    expect($state['success'])->toBeFalse()
        ->and($state['error'])->toContain('sam:diagnose');
});
