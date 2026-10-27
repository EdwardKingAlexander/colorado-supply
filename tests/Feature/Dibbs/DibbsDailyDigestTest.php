<?php

use App\Jobs\DibbsDailyDigestJob;
use App\Jobs\DibbsDownloadJob;
use App\Models\Company;
use App\Models\DibbsApprovedSource;
use App\Models\DibbsFile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqTracking;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Notifications\DibbsDailyDigestNotification;
use App\Services\Dibbs\DibbsDigestBuilder;
use App\Services\Dibbs\DibbsRfqMatcher;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Company::factory()->create();
    Notification::fake();
    Cache::flush();
    config([
        'dibbs.digest.enabled' => true,
        'dibbs.digest.recipients' => ['ops@example.test', 'owner@example.test'],
        'dibbs.digest.min_match' => 'product_codes',
        'dibbs.digest.section_limit' => 25,
        'dibbs.digest.closing_within_days' => 3,
    ]);

    // Fixed name: must never equal a fixture file (in261002.txt) the download test imports.
    $this->file = DibbsFile::factory()->create(['posting_date' => today()->subDay(), 'name' => 'in991231.txt', 'digest_sent_at' => null]);
    $old = DibbsFile::factory()->create(['posting_date' => today()->subDays(5), 'digest_sent_at' => now()->subDays(4)]);

    $new = fn (array $attrs) => DibbsRfq::factory()->create($attrs + ['first_seen_file_id' => $this->file->id, 'return_by_date' => today()->addDays(10)]);

    $this->catalogRfq = $new(['nsn' => '5305012345678', 'fsc' => '5305', 'nomenclature' => 'SCREW,CAP']);
    $this->approvedRfq = $new(['nsn' => '4820015550101', 'fsc' => '4820', 'nomenclature' => 'VALVE,GATE']);
    $this->codesRfq = $new(['nsn' => '5310011112222', 'fsc' => '5310', 'nomenclature' => 'NUT,PLAIN']);
    $this->outsideRfq = $new(['nsn' => '1560011112222', 'fsc' => '1560', 'nomenclature' => 'PANEL']);
    $this->noBidRfq = $new(['nsn' => '5320011112222', 'fsc' => '5320', 'nomenclature' => 'RIVET']);
    DibbsRfqTracking::for($this->noBidRfq)->update(['status' => 'no_bid']);
    $this->closingRfq = DibbsRfq::factory()->create(['nsn' => '5330011112222', 'fsc' => '5330', 'nomenclature' => 'GASKET', 'first_seen_file_id' => $old->id, 'return_by_date' => today()->addDay()]);
    $this->closingReviewed = DibbsRfq::factory()->create(['nsn' => '5340011112222', 'fsc' => '5340', 'nomenclature' => 'BRACKET', 'first_seen_file_id' => $old->id, 'return_by_date' => today()->addDay()]);
    DibbsRfqTracking::for($this->closingReviewed)->update(['status' => 'quoting']);

    $this->closingMatched = DibbsRfq::factory()->create(['nsn' => '4820017778888', 'fsc' => '4820', 'nomenclature' => 'VALVE,CHECK', 'first_seen_file_id' => $old->id, 'return_by_date' => today()->addDays(2)]);

    $maker = Manufacturer::create(['name' => 'Example Valve Co', 'cage_code' => '79227']);
    DibbsApprovedSource::create(['nsn' => '4820017778888', 'cage_code' => '79227', 'part_number' => 'CV-1']);
    MilSpecPart::create(['nsn' => '5305-01-234-5678', 'description' => 'SCREW', 'manufacturer_id' => $maker->id]);
    DibbsApprovedSource::create(['nsn' => '4820015550101', 'cage_code' => '79227', 'part_number' => 'GV-200']);
    (new DibbsRfqMatcher)->rebuild();
});

function sectionNames(array $digest, string $key): array
{
    return array_column($digest['sections'][$key]['items'] ?? [], 'nomenclature');
}

test('builds strongest-first sections, each RFQ once, without no-bid or outside RFQs', function () {
    $digest = (new DibbsDigestBuilder)->build($this->file);

    expect(array_keys($digest['sections']))->toBe(['catalog', 'approved', 'product_codes', 'closing'])
        ->and(sectionNames($digest, 'catalog'))->toBe(['SCREW,CAP'])
        ->and(sectionNames($digest, 'approved'))->toBe(['VALVE,GATE'])
        ->and(sectionNames($digest, 'product_codes'))->toBe(['NUT,PLAIN'])
        // Closing soon: the RFQ we're quoting and the untouched matched one; not the
        // untouched, unmatched GASKET (product-code-only RFQs would flood this section).
        ->and(sectionNames($digest, 'closing'))->toBe(['BRACKET', 'VALVE,CHECK'])
        ->and($digest['sections']['catalog']['items'][0]['admin_url'])->toEndWith('/admin/dibbs-rfqs/'.$this->catalogRfq->id);
});

test('min_match narrows the digest to stronger signals', function () {
    config(['dibbs.digest.min_match' => 'catalog']);

    $digest = (new DibbsDigestBuilder)->build($this->file);

    // RFQs we're already working stay in "closing soon" whatever min_match says;
    // the approved-source-only VALVE,CHECK drops out.
    expect(array_keys($digest['sections']))->toBe(['catalog', 'closing'])
        ->and(sectionNames($digest, 'catalog'))->toBe(['SCREW,CAP'])
        ->and(sectionNames($digest, 'closing'))->toBe(['BRACKET']);
});

test('caps long sections and reports the remainder', function () {
    config(['dibbs.digest.section_limit' => 1]);
    DibbsRfq::factory()->count(3)->create(['fsc' => '5310', 'first_seen_file_id' => $this->file->id, 'return_by_date' => today()->addDays(12)]);

    $digest = (new DibbsDigestBuilder)->build($this->file);

    expect($digest['sections']['product_codes']['total'])->toBe(4)
        ->and($digest['sections']['product_codes']['items'])->toHaveCount(1);
});

test('sends once per posting file and once per day, to every recipient', function () {
    expect(app()->call([new DibbsDailyDigestJob, 'handle']))->toBe('sent');

    Notification::assertSentOnDemand(DibbsDailyDigestNotification::class, function ($n, $channels, AnonymousNotifiable $notifiable) {
        return $notifiable->routes['mail'] === ['ops@example.test', 'owner@example.test']
            && $n->subject() === 'DIBBS RFQs: 3 new, 2 closing soon ('.today()->subDay()->format('M j').')';
    });

    expect($this->file->fresh()->digest_sent_at)->not->toBeNull()
        ->and(app()->call([new DibbsDailyDigestJob, 'handle']))->toBe('already_sent_today');

    Notification::assertSentOnDemandTimes(DibbsDailyDigestNotification::class, 1);
});

test('with no new file it sends only closing-soon RFQs, and nothing when none are closing', function () {
    $this->file->update(['digest_sent_at' => now()->subHour()]);

    expect(app()->call([new DibbsDailyDigestJob, 'handle']))->toBe('sent');
    Notification::assertSentOnDemand(DibbsDailyDigestNotification::class,
        fn ($n) => array_keys($n->digest['sections']) === ['closing'] && $n->digest['new_file'] === false);

    Cache::flush();
    $this->closingReviewed->update(['return_by_date' => today()->addDays(9)]);
    $this->closingMatched->update(['return_by_date' => today()->addDays(9)]);
    Notification::fake();

    expect(app()->call([new DibbsDailyDigestJob, 'handle']))->toBe('empty');
    Notification::assertNothingSent();
});

test('renders the email with links to the admin RFQ and DIBBS', function () {
    $mail = (new DibbsDailyDigestNotification((new DibbsDigestBuilder)->build($this->file)))->toMail(new AnonymousNotifiable);
    $html = (string) $mail->render();

    expect($html)->toContain('In your catalog (1)')
        ->toContain('SCREW,CAP')
        ->toContain('/admin/dibbs-rfqs/'.$this->catalogRfq->id)
        ->toContain('rfqrec.aspx?sn='.$this->catalogRfq->solicitation_number)
        ->toContain('Closing soon (2)')
        ->toContain('Quoting');
});

test('skips quietly when disabled or without recipients', function () {
    config(['dibbs.digest.enabled' => false]);
    expect(app()->call([new DibbsDailyDigestJob, 'handle']))->toBe('disabled');

    config(['dibbs.digest.enabled' => true, 'dibbs.digest.recipients' => [], 'dibbs.alert_email' => null]);
    expect(app()->call([new DibbsDailyDigestJob, 'handle']))->toBe('no_recipients');

    Notification::assertNothingSent();
});

test('the download job queues the digest after importing a new index file', function () {
    Queue::fake();
    Storage::fake('local');
    fakeDibbs(['in261002.txt' => dibbsFixture('in261002.txt')], listedDays: ['261002']);

    app()->call([new DibbsDownloadJob, 'handle']);

    Queue::assertPushed(DibbsDailyDigestJob::class);
});

test('a fallback digest run is scheduled at 08:30 Mountain', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'dibbs-digest');

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('30 8 * * *')
        ->and($event->timezone)->toBe('America/Denver');
});
