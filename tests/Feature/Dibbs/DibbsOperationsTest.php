<?php

use App\Filament\Pages\DibbsUpload;
use App\Jobs\DibbsDownloadJob;
use App\Models\Admin;
use App\Models\Company;
use App\Models\DibbsApprovedSource;
use App\Models\DibbsFile;
use App\Models\DibbsQuoteTemplate;
use App\Models\DibbsQuotingProfile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqMatch;
use App\Models\DibbsRfqTracking;
use App\Models\Manufacturer;
use App\Notifications\DibbsDailyDigestNotification;
use App\Notifications\DibbsIngestionFailedNotification;
use App\Services\Dibbs\BatchQuoteExporter;
use App\Services\Dibbs\DibbsHealth;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    Cache::flush();
    config(['dibbs.enabled' => true, 'dibbs.alert_email' => 'ops@example.test']);
    $this->company = Company::factory()->create();
});

describe('health', function () {
    test('counts business days only', function () {
        $health = new DibbsHealth;

        // Fri Oct 2 → Mon Oct 5 is one business day; → Wed Oct 7 is three.
        expect($health->businessDaysBetween(CarbonImmutable::parse('2026-10-02'), CarbonImmutable::parse('2026-10-05')))->toBe(1)
            ->and($health->businessDaysBetween(CarbonImmutable::parse('2026-10-02'), CarbonImmutable::parse('2026-10-07')))->toBe(3)
            ->and($health->businessDaysBetween(CarbonImmutable::parse('2026-10-02'), CarbonImmutable::parse('2026-10-04')))->toBe(0);
    });

    test('is stale after more than two business days, or with no file at all', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-07 12:00', 'UTC')); // Wednesday

        expect((new DibbsHealth)->status()['stale'])->toBeTrue();

        DibbsFile::factory()->create(['posting_date' => '2026-10-05']); // Mon: 2 business days → fine
        expect((new DibbsHealth)->status())->toMatchArray(['stale' => false, 'business_days_behind' => 2]);

        DibbsFile::query()->update(['posting_date' => '2026-10-02']); // Fri: 3 business days → stale
        expect((new DibbsHealth)->status()['stale'])->toBeTrue();
    });

    test('the download job records each run and counts consecutive failures', function () {
        fakeDibbs([], listedDays: ['261002']);
        $listing = config('dibbs.listing_url');
        config(['dibbs.listing_url' => 'https://www.dibbs.bsm.dla.mil/RFQ/Moved.aspx']); // outage: listing 404s

        app()->call([new DibbsDownloadJob, 'handle']);
        app()->call([new DibbsDownloadJob, 'handle']);

        $status = (new DibbsHealth)->status();
        expect($status['last_run']['ok'])->toBeFalse()
            ->and($status['last_run']['error'])->toContain('listing')
            ->and($status['consecutive_failures'])->toBe(2);

        config(['dibbs.listing_url' => $listing]);
        app()->call([new DibbsDownloadJob, 'handle']);

        expect((new DibbsHealth)->status()['consecutive_failures'])->toBe(0);
    });

    test('check-freshness alerts when stale and stays quiet when current', function () {
        $this->travelTo(CarbonImmutable::parse('2026-10-07 12:00', 'UTC'));
        DibbsFile::factory()->create(['posting_date' => '2026-10-01']);

        $this->artisan('dibbs:check-freshness')->assertFailed();
        Notification::assertSentOnDemand(DibbsIngestionFailedNotification::class, fn ($n) => str_contains($n->reason, 'No new DIBBS RFQ file since Oct 1'));

        Notification::fake();
        DibbsFile::query()->update(['posting_date' => '2026-10-06']);

        $this->artisan('dibbs:check-freshness')->assertSuccessful();
        Notification::assertNothingSent();
    });

    test('the upload page shows ingestion health', function () {
        Filament::setCurrentPanel('admin');
        $this->actingAs(Admin::factory()->create(), 'admin');
        DibbsHealth::recordRun(false, ['failed' => ['in261005.txt: layout mismatch']], null);

        Livewire::test(DibbsUpload::class)
            ->assertSeeHtml('data-testid="dibbs-health"')
            ->assertSee('Consecutive failures')
            ->assertSee('DIBBS data is behind');
    });
});

describe('prune', function () {
    beforeEach(function () {
        $this->oldUntouched = DibbsRfq::factory()->create(['nsn' => '5305000000001', 'return_by_date' => today()->subDays(200)]);
        $this->oldTracked = DibbsRfq::factory()->create(['nsn' => '5305000000002', 'return_by_date' => today()->subDays(200)]);
        $this->oldMatched = DibbsRfq::factory()->create(['nsn' => '5305000000003', 'return_by_date' => today()->subDays(200)]);
        $this->recent = DibbsRfq::factory()->create(['nsn' => '5305000000004', 'return_by_date' => today()->subDays(10)]);
        $this->oldNewOnly = DibbsRfq::factory()->create(['nsn' => '5305000000005', 'return_by_date' => today()->subDays(200)]);

        // Tracked by another company: still protected.
        $other = Company::factory()->create();
        DibbsRfqTracking::withoutGlobalScopes()->create(['company_id' => $other->id, 'dibbs_rfq_id' => $this->oldTracked->id, 'status' => 'quoted']);
        DibbsRfqTracking::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'dibbs_rfq_id' => $this->oldNewOnly->id, 'status' => 'new']);
        $maker = Manufacturer::create(['name' => 'M', 'cage_code' => '12345']);
        DibbsRfqMatch::withoutGlobalScopes()->create(['company_id' => $other->id, 'dibbs_rfq_id' => $this->oldMatched->id, 'reason' => 'approved_source', 'matchable_type' => Manufacturer::class, 'matchable_id' => $maker->id, 'score' => 70]);

        DibbsQuoteTemplate::create(['solicitation_number' => $this->oldUntouched->solicitation_number, 'purchase_request' => $this->oldUntouched->purchase_request, 'dibbs_rfq_id' => $this->oldUntouched->id, 'raw_row' => '"x"']);
        DibbsApprovedSource::create(['nsn' => '5305000000001', 'cage_code' => '99999', 'part_number' => 'GONE']);
        DibbsApprovedSource::create(['nsn' => '5305000000004', 'cage_code' => '99999', 'part_number' => 'KEEP']);
    });

    test('a dry run deletes nothing', function () {
        $this->artisan('dibbs:prune --dry-run')->expectsOutputToContain('Would delete 2 RFQ(s)')->assertSuccessful();

        expect(DibbsRfq::count())->toBe(5);
    });

    test('deletes only old RFQs nobody acted on and nothing matched, with their rows and orphaned sources', function () {
        $this->artisan('dibbs:prune')->assertSuccessful();

        expect(DibbsRfq::pluck('nsn')->sort()->values()->all())->toBe(['5305000000002', '5305000000003', '5305000000004'])
            ->and(DibbsQuoteTemplate::count())->toBe(0)
            ->and(DibbsApprovedSource::pluck('part_number')->all())->toBe(['KEEP']);
    });

    test('removes raw files past the window but keeps the import record', function () {
        Storage::disk('local')->put('dibbs/old-in260101.txt', 'x');
        $file = DibbsFile::factory()->create(['stored_path' => 'dibbs/old-in260101.txt', 'imported_at' => now()->subDays(90)]);

        $this->artisan('dibbs:prune')->assertSuccessful();

        Storage::disk('local')->assertMissing('dibbs/old-in260101.txt');
        expect($file->fresh())->not->toBeNull()->and($file->fresh()->stored_path)->toBeNull();
    });

    test('is scheduled weekly, with a weekday freshness check', function () {
        $events = collect(app(Schedule::class)->events())->keyBy('description');

        expect($events['dibbs-prune']->expression)->toBe('0 3 * * 0')
            ->and($events['dibbs-freshness']->expression)->toBe('0 10 * * 1-5')
            ->and($events['dibbs-freshness']->timezone)->toBe('America/Denver');
    });
});

test('end to end: download → import → match → digest → worksheet → export', function () {
    config(['dibbs.digest.recipients' => ['ops@example.test'], 'dibbs.digest.enabled' => true]);
    $admin = Admin::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-10-03 13:00', 'UTC')); // the morning after the 10-02 posting

    // We buy from an approved source on the first fixture RFQ's NSN.
    $nsn = '8465015148739';
    $approvedCage = trim(explode('","', trim(collect(explode("\r\n", dibbsFixture('as261002.txt')))->first(fn ($l) => str_contains($l, $nsn)), '"'))[1]);
    Manufacturer::create(['name' => 'Approved Maker', 'cage_code' => $approvedCage]);

    fakeDibbs(['in261002.txt' => dibbsFixture('in261002.txt'), 'bq261002.zip' => dibbsBqZip()], listedDays: ['261002']);

    // 1. The morning download imports, matches, and (sync queue) sends the digest.
    $summary = app()->call([new DibbsDownloadJob, 'handle']);
    expect($summary['downloaded'])->toBe(['in261002.txt', 'bq261002.zip']);

    $rfq = DibbsRfq::where('nsn', $nsn)->orderBy('purchase_request')->first();
    expect($rfq->matches->pluck('reason')->all())->toContain(DibbsRfqMatch::REASON_APPROVED_SOURCE);

    Notification::assertSentOnDemand(DibbsDailyDigestNotification::class, fn ($n) => isset($n->digest['sections']['approved'])
        && collect($n->digest['sections']['approved']['items'])->pluck('nsn')->contains('8465-01-514-8739'));

    // 2. Someone confirms the profile and starts a quote; the approved part is pre-filled.
    $profile = DibbsQuotingProfile::create([
        'company_id' => $this->company->id, 'quoter_cage' => '1ABC2', 'quote_for_cage' => '1ABC2', 'small_business_code' => 'B',
        'affirmative_action_code' => 'NA', 'previous_contracts_code' => 'NA', 'adr_code' => 'A', 'child_labor_code' => 'N',
        'default_hazardous_material' => 'N', 'default_material_code' => '0', 'default_manufacturer_dealer' => 'DD',
        'confirmed_by' => $admin->id, 'confirmed_at' => now(),
    ]);
    $tracking = DibbsRfqTracking::for($rfq)->startQuoting();
    $tracking->update(['unit_price' => '41.20']);

    expect($tracking->part_offered_cage)->toBe($approvedCage);

    // 3. Export produces a DIBBS file and marks the RFQ quoted.
    $result = app(BatchQuoteExporter::class)->export(collect([$tracking->fresh()]), $profile, $admin->id);

    expect(reset($result['files']))->toContain('"'.$rfq->solicitation_number.'"')->toContain('"41.20"')->toContain('"'.$approvedCage.'"')
        ->and($tracking->fresh()->status)->toBe('quoted')
        ->and((new DibbsHealth)->status()['last_run']['ok'])->toBeTrue();
});
