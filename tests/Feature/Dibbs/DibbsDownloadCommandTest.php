<?php

use App\Jobs\DibbsDailyDigestJob;
use App\Models\DibbsRfq;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    Queue::fake([DibbsDailyDigestJob::class]);
    config(['dibbs.enabled' => true, 'dibbs.backfill_days' => 5]);
});

test('downloads and imports now, reporting what happened', function () {
    fakeDibbs(['in261002.txt' => dibbsFixture('in261002.txt'), 'bq261002.zip' => dibbsBqZip()], listedDays: ['261002', '261001']);

    $this->artisan('dibbs:download')
        ->expectsOutputToContain('Checking the last 5 posting day(s)')
        ->expectsTable(['Result', 'Files'], [
            ['Imported', '2: in261002.txt, bq261002.zip'],
            ['Already imported', '0'],
            ['Not posted yet', '2: in261001.txt, bq261001.zip'],
            ['Failed', '0'],
        ])
        ->assertSuccessful();

    expect(DibbsRfq::count())->toBe(50);
    Queue::assertPushed(DibbsDailyDigestJob::class);
});

test('--days widens the catch-up window for a first fill', function () {
    $days = ['261002', '261001', '260930', '260929', '260928', '260925', '260924'];
    fakeDibbs([], listedDays: $days);

    $this->artisan('dibbs:download --days=7')
        ->expectsOutputToContain('Checking the last 7 posting day(s)')
        ->expectsOutputToContain('Not posted yet')
        ->assertSuccessful();
});

test('rejects an invalid --days', function (string $days) {
    $this->artisan("dibbs:download --days={$days}")->assertExitCode(2);
})->with(['0', '31', 'abc']);

test('respects DIBBS_ENABLED unless forced', function () {
    config(['dibbs.enabled' => false]);
    fakeDibbs([], listedDays: ['261002']);

    $this->artisan('dibbs:download')->expectsOutputToContain('--force')->assertFailed();
    $this->artisan('dibbs:download --force')->assertSuccessful();
});

test('fails clearly when DIBBS cannot be read', function () {
    fakeDibbs([], listedDays: ['261002']);
    config(['dibbs.listing_url' => 'https://www.dibbs.bsm.dla.mil/RFQ/Moved.aspx']);

    $this->artisan('dibbs:download')->expectsOutputToContain('Could not read the DIBBS RFQ listing')->assertFailed();
});
