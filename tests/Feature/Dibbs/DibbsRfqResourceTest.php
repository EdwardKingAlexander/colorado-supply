<?php

use App\Filament\Resources\DibbsRfqResource\Pages\ListDibbsRfqs;
use App\Filament\Resources\DibbsRfqResource\Pages\ViewDibbsRfq;
use App\Filament\Resources\DibbsRfqResource\Widgets\DibbsRfqStats;
use App\Models\Admin;
use App\Models\Company;
use App\Models\DibbsApprovedSource;
use App\Models\DibbsFile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqTracking;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->obLevel = ob_get_level();
    $this->company = Company::factory()->create();
    $this->admin = Admin::factory()->create(['name' => 'Dana Ops']);
    $this->actingAs($this->admin, 'admin');

    // In our product codes (5305 is a configured PSC; 53xx and 48xx are configured groups) vs not (1560 aircraft structures).
    $this->screws = DibbsRfq::factory()->create(['fsc' => '5305', 'nsn' => '5305012345678', 'nomenclature' => 'SCREW,CAP', 'return_by_date' => today()->addDays(2), 'amsc' => 'G']);
    $this->valve = DibbsRfq::factory()->create(['fsc' => '4820', 'nsn' => '4820098765432', 'nomenclature' => 'VALVE,GATE', 'return_by_date' => today()->addDays(10), 'amsc' => 'C']);
    $this->aircraft = DibbsRfq::factory()->create(['fsc' => '1560', 'nsn' => '1560011112222', 'nomenclature' => 'PANEL,STRUCTURAL', 'return_by_date' => today()->addDays(5)]);
    $this->closed = DibbsRfq::factory()->create(['fsc' => '5310', 'nsn' => '5310033334444', 'nomenclature' => 'NUT,PLAIN', 'return_by_date' => today()->subDay()]);
});

afterEach(function () {
    while (ob_get_level() > $this->obLevel) {
        ob_end_clean();
    }
});

test('defaults to open RFQs in our product codes, soonest deadline first', function () {
    Livewire::test(ListDibbsRfqs::class)
        ->assertCanSeeTableRecords([$this->screws, $this->valve], inOrder: true)
        ->assertCanNotSeeTableRecords([$this->aircraft, $this->closed])
        ->assertTableColumnExists('mobile_summary');
});

test('the product-code scope matches 4-character PSCs and 2-character groups from config', function () {
    config(['sam_opportunities.psc_codes' => ['1560'], 'sam_opportunities.psc_groups' => ['48'], 'sam_opportunities.psc_groups_optional' => []]);

    expect(DibbsRfq::inOurProductCodes()->pluck('fsc')->sort()->values()->all())->toBe(['1560', '4820']);
});

test('filters by competition and treats untracked RFQs as new', function () {
    DibbsRfqTracking::for($this->valve)->update(['status' => 'quoting']);

    Livewire::test(ListDibbsRfqs::class)
        ->filterTable('competition', 'open')
        ->assertCanSeeTableRecords([$this->screws])
        ->assertCanNotSeeTableRecords([$this->valve])
        ->resetTableFilters()
        ->filterTable('status', ['new'])
        ->assertCanSeeTableRecords([$this->screws])
        ->assertCanNotSeeTableRecords([$this->valve])
        ->filterTable('status', ['quoting'])
        ->assertCanSeeTableRecords([$this->valve])
        ->assertCanNotSeeTableRecords([$this->screws]);
});

test('setting a status creates a company-scoped tracking row and logs who changed it', function () {
    Livewire::test(ListDibbsRfqs::class)
        ->callTableAction('setStatus', $this->screws, ['status' => 'reviewing'])
        ->assertNotified();

    $tracking = DibbsRfqTracking::where('dibbs_rfq_id', $this->screws->id)->sole();

    expect($tracking->status)->toBe('reviewing')
        ->and($tracking->company_id)->toBe(Company::orderBy('id')->value('id'));

    $activity = Activity::where('subject_type', DibbsRfqTracking::class)->where('subject_id', $tracking->id)->latest('id')->first();
    expect($activity->causer_id)->toBe($this->admin->id)
        ->and($activity->properties['attributes']['status'])->toBe('reviewing');
});

test('assigns an owner and saves notes', function () {
    Livewire::test(ListDibbsRfqs::class)
        ->callTableAction('assign', $this->valve, ['assigned_to' => $this->admin->id])
        ->callTableAction('note', $this->valve, ['notes' => 'Ask Parker for approved P/N pricing']);

    $tracking = DibbsRfqTracking::where('dibbs_rfq_id', $this->valve->id)->sole();
    expect($tracking->assigned_to)->toBe($this->admin->id)
        ->and($tracking->notes)->toBe('Ask Parker for approved P/N pricing');
});

test('bulk marks RFQs no-bid', function () {
    Livewire::test(ListDibbsRfqs::class)
        ->callTableBulkAction('bulk_no_bid', [$this->screws, $this->valve]);

    expect(DibbsRfqTracking::whereIn('dibbs_rfq_id', [$this->screws->id, $this->valve->id])->pluck('status')->unique()->all())->toBe(['no_bid']);
});

test('the detail page shows DIBBS links, approved sources and history', function () {
    DibbsApprovedSource::create(['nsn' => $this->valve->nsn, 'cage_code' => '79227', 'part_number' => 'RK 800M4-T 1-1/2"', 'company_name' => 'Example Valve Co']);
    DibbsRfqTracking::for($this->valve)->update(['status' => 'reviewing']);

    Livewire::test(ViewDibbsRfq::class, ['record' => $this->valve->getRouteKey()])
        ->assertSee('4820-09-876-5432')
        ->assertSee('Approved sources only')
        ->assertSee('79227')
        ->assertSee('RK 800M4-T 1-1/2"')
        ->assertSee('rfqrec.aspx?sn='.$this->valve->solicitation_number, escape: false)
        ->assertSee('Downloads/RFQ/'.substr($this->valve->solicitation_number, -1).'/', escape: false)
        ->assertSee('status → Reviewing')
        ->assertSee('Dana Ops');
});

test('DIBBS links follow the verified patterns', function () {
    $rfq = DibbsRfq::factory()->make(['solicitation_number' => 'SPE1C126T1775', 'nsn' => '8465015148739', 'item_type' => '1']);

    expect($rfq->dibbsRecordUrl())->toBe('https://www.dibbs.bsm.dla.mil/rfq/rfqrec.aspx?sn=SPE1C126T1775')
        ->and($rfq->solicitationPdfUrl())->toBe('https://dibbs2.bsm.dla.mil/Downloads/RFQ/5/SPE1C126T1775.PDF')
        ->and($rfq->dibbsNsnHistoryUrl())->toBe('https://www.dibbs.bsm.dla.mil/RFQ/RFQNsn.aspx?value=8465015148739')
        ->and($rfq->formattedNsn())->toBe('8465-01-514-8739');
});

test('the stats widget counts the pipeline', function () {
    $file = DibbsFile::factory()->create(['posting_date' => today()->subDay()]);
    $this->screws->update(['first_seen_file_id' => $file->id]);
    DibbsRfqTracking::for($this->valve)->update(['status' => 'quoting']);

    Livewire::test(DibbsRfqStats::class)
        ->assertSee('New in latest file')
        ->assertSeeInOrder(['Closing in 3 days', '1'])
        ->assertSeeInOrder(['Quoting', '1']);
});
