<?php

use App\Filament\Pages\DibbsUpload;
use App\Jobs\DibbsDailyDigestJob;
use App\Jobs\DibbsDownloadJob;
use App\Models\Admin;
use App\Models\DibbsFile;
use App\Models\DibbsQuoteTemplate;
use App\Models\DibbsRfq;
use App\Notifications\DibbsIngestionFailedNotification;
use App\Services\Dibbs\DibbsAccessException;
use App\Services\Dibbs\DibbsClient;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
| A fake DIBBS shaped like the real one (ai/modules/dibbs-rfq-tracking/findings.md):
| a DoD consent banner per host until accepted, the "Recent RFQs" listing on www,
| files on dibbs2, and missing files answered with a 404.
*/

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    config(['dibbs.alert_email' => 'ops@example.test', 'dibbs.enabled' => true, 'dibbs.backfill_days' => 5]);
    Http::preventStrayRequests();
});

describe('client', function () {
    test('accepts the consent banner once per host and lists posting days newest first', function () {
        $state = fakeDibbs();

        $days = (new DibbsClient)->listRecentFiles();

        expect($days)->toHaveCount(2)
            ->and($days[0]['posting_date']->toDateString())->toBe('2026-10-02')
            ->and($days[0]['in'])->toBe('https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/in261002.txt')
            ->and($days[0]['bq'])->toBe('https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/bq261002.zip')
            ->and($state->accepted)->toHaveKey('www.dibbs.bsm.dla.mil');
    });

    test('identifies itself honestly, never as a browser', function () {
        $state = fakeDibbs();
        config(['dibbs.user_agent' => 'ColoradoSupplyRfqTracker/1.0']);

        (new DibbsClient)->listRecentFiles();

        expect(array_unique($state->userAgents))->toBe(['ColoradoSupplyRfqTracker/1.0']);
    });

    test('downloads a file through the second host\'s own banner, and returns null when missing', function () {
        $state = fakeDibbs(['in261002.txt' => dibbsFixture('in261002.txt')]);
        $client = new DibbsClient;

        expect($client->download('https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/in261002.txt'))->toBe(dibbsFixture('in261002.txt'))
            ->and($state->accepted)->toHaveKey('dibbs2.bsm.dla.mil')
            ->and($client->download('https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/in261004.txt'))->toBeNull();
    });

    test('treats a redirect on a download as missing instead of following it', function () {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://dibbs2.bsm.dla.mil:80/nowhere'])]);

        expect((new DibbsClient)->download('https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/in261004.txt'))->toBeNull();

        Http::assertSentCount(1);
    });

    test('fails loudly when the banner will not go away', function () {
        fakeDibbs(bannerSticks: true);

        (new DibbsClient)->listRecentFiles();
    })->throws(DibbsAccessException::class, 'consent banner');

    test('refuses to fetch from hosts other than DIBBS', function () {
        (new DibbsClient)->download('https://example.com/in261002.txt');
    })->throws(DibbsAccessException::class, 'non-DIBBS');
});

describe('download job', function () {
    test('downloads and imports listed days, then skips them on the next run', function () {
        Queue::fake([DibbsDailyDigestJob::class]); // the digest has its own tests
        fakeDibbs(['in261002.txt' => dibbsFixture('in261002.txt'), 'bq261002.zip' => dibbsBqZip()]);

        $first = app()->call([new DibbsDownloadJob, 'handle']);
        Queue::assertPushed(DibbsDailyDigestJob::class);

        expect($first['downloaded'])->toBe(['in261002.txt', 'bq261002.zip'])
            ->and($first['missing'])->toBe(['in261001.txt', 'bq261001.zip'])
            ->and($first['failed'])->toBe([])
            ->and(DibbsRfq::count())->toBe(50)
            ->and(DibbsQuoteTemplate::whereNull('dibbs_rfq_id')->count())->toBe(0)
            ->and(DibbsFile::where('source', 'automated')->count())->toBe(2);

        $second = app()->call([new DibbsDownloadJob, 'handle']);

        expect($second['skipped'])->toBe(['in261002.txt', 'bq261002.zip'])
            ->and($second['downloaded'])->toBe([]);

        Notification::assertNothingSent();
    });

    test('alerts when the listing cannot be read', function () {
        Http::fake(['*' => Http::response('Service Unavailable', 503)]);

        app()->call([new DibbsDownloadJob, 'handle']);

        Notification::assertSentOnDemand(DibbsIngestionFailedNotification::class,
            fn ($n) => str_contains($n->reason, 'listing') && str_contains($n->reason, '503'));
    });

    test('alerts when a downloaded file fails to import', function () {
        $broken = implode("\r\n", array_map(fn ($l) => $l.'XXXX', explode("\r\n", trim(dibbsFixture('in261002.txt')))));
        fakeDibbs(['in261002.txt' => $broken], listedDays: ['261002']);

        $summary = app()->call([new DibbsDownloadJob, 'handle']);

        expect($summary['failed'][0])->toContain('in261002.txt')->toContain('layout mismatch');
        Notification::assertSentOnDemand(DibbsIngestionFailedNotification::class);
    });

    test('does nothing when disabled', function () {
        config(['dibbs.enabled' => false]);

        app()->call([new DibbsDownloadJob, 'handle']);

        Http::assertNothingSent();
    });

    test('is scheduled daily at 07:00 Mountain', function () {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'dibbs-download');

        expect($event)->not->toBeNull()
            ->and($event->expression)->toBe('0 7 * * *')
            ->and($event->timezone)->toBe('America/Denver');
    });
});

describe('upload page', function () {
    beforeEach(function () {
        Filament::setCurrentPanel('admin');
        $this->obLevel = ob_get_level();
        $this->actingAs(Admin::factory()->create(), 'admin');
    });

    afterEach(function () {
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }
    });

    test('imports uploaded daily files and lists them', function () {
        Livewire::test(DibbsUpload::class)
            ->fillForm(['files' => [
                UploadedFile::fake()->createWithContent('in261002.txt', dibbsFixture('in261002.txt')),
                UploadedFile::fake()->createWithContent('bq261002.zip', dibbsBqZip()),
            ]])
            ->call('import')
            ->assertNotified('Imported')
            ->assertSee('in261002.txt')
            ->assertSee('bq261002.zip');

        expect(DibbsRfq::count())->toBe(50)
            ->and(DibbsFile::where('source', 'upload')->count())->toBe(2);
    });

    test('re-uploading the same file is a no-op', function () {
        foreach ([1, 2] as $_) {
            Livewire::test(DibbsUpload::class)
                ->fillForm(['files' => [UploadedFile::fake()->createWithContent('in261002.txt', dibbsFixture('in261002.txt'))]])
                ->call('import');
        }

        expect(DibbsFile::count())->toBe(1)->and(DibbsRfq::count())->toBe(50);
    });

    test('explains a file that is not a DIBBS daily file', function () {
        Livewire::test(DibbsUpload::class)
            ->fillForm(['files' => [UploadedFile::fake()->createWithContent('rfqs.txt', 'hello')]])
            ->call('import')
            ->assertNotified('Some files were not imported');

        expect(DibbsFile::count())->toBe(0);
    });
});
