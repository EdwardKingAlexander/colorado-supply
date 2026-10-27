<?php

use App\Filament\Pages\DibbsQuoteWorksheet;
use App\Filament\Pages\DibbsQuotingProfilePage;
use App\Filament\Resources\DibbsRfqResource\Pages\ListDibbsRfqs;
use App\Models\Admin;
use App\Models\Company;
use App\Models\DibbsQuoteTemplate;
use App\Models\DibbsQuotingProfile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqTracking;
use App\Models\Manufacturer;
use App\Services\Dibbs\BatchQuoteExporter;
use App\Services\Dibbs\BatchQuoteFileParser;
use App\Services\Dibbs\DibbsFileImporter;
use App\Services\Dibbs\DibbsRfqMatcher;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
| Real DLA rows from tests/Fixtures/dibbs (2026-10-02). SPE1C126T1775 / PR 7018426027:
| small-business set-aside, approved-source item (col 105 = P), no higher-level
| quality requirement (col 117 = N), DLA delivery 20 days, qty 25.
*/

beforeEach(function () {
    Storage::fake('local');
    $this->company = Company::factory()->create();
    $this->admin = Admin::factory()->create(['name' => 'Dana Ops']);

    $importer = new DibbsFileImporter;
    $importer->import($importer->register(dibbsFixture('in261002.txt'), 'in261002.txt', 'upload'));
    $importer->import($importer->register(dibbsBqZip(), 'bq261002.zip', 'upload'));

    $this->profile = DibbsQuotingProfile::create([
        'company_id' => $this->company->id,
        'quoter_cage' => '1ABC2', 'quote_for_cage' => '1ABC2', 'small_business_code' => 'B',
        'affirmative_action_code' => 'NA', 'previous_contracts_code' => 'NA', 'adr_code' => 'A',
        'child_labor_code' => 'N', 'default_hazardous_material' => 'N', 'default_material_code' => '0',
        'default_manufacturer_dealer' => 'DD', 'confirmed_by' => $this->admin->id, 'confirmed_at' => now(),
    ]);

    $this->rfq = DibbsRfq::where('solicitation_number', 'SPE1C126T1775')->where('purchase_request', '7018426027')->sole();
    $this->approved = $this->rfq->approvedSources()->first();
});

function readyTracking(DibbsRfq $rfq, array $overrides = []): DibbsRfqTracking
{
    $approved = $rfq->approvedSources()->first();

    $tracking = DibbsRfqTracking::create(array_merge([
        'company_id' => Company::orderBy('id')->value('id'),
        'dibbs_rfq_id' => $rfq->id,
        'status' => 'quoting',
        'unit_price' => '12.5',
        'delivery_days' => 20,
        'manufacturing_source_cage' => $approved?->cage_code,
        'part_offered_code' => $approved ? '1' : null,
        'part_offered_cage' => $approved?->cage_code,
        'part_offered_number' => $approved?->part_number,
    ], $overrides));

    return $tracking->load('rfq.quoteTemplates');
}

test('fills only the vendor columns of DLA\'s row; every other column is byte-for-byte unchanged', function () {
    $tracking = readyTracking($this->rfq);
    $template = $tracking->rfq->quoteTemplates->sortBy('row_index')->first();
    $original = BatchQuoteFileParser::splitRow($template->raw_row);

    $line = (new BatchQuoteExporter)->fill($template, $tracking, $this->profile);
    $filled = BatchQuoteFileParser::splitRow($line);

    // DLA column number => expected value (the golden expectation, written out by hand).
    $expected = [
        6 => '1ABC2', 7 => '1ABC2', 13 => 'B', 18 => '', 21 => 'NA', 22 => 'NA', 23 => 'A',
        28 => 'Y', 50 => '12.50', 51 => '20', 65 => 'N', 67 => '0', 70 => 'D', 71 => '', 72 => '',
        102 => 'DD', 103 => $this->approved->cage_code,
        106 => '1', 107 => $this->approved->cage_code, 108 => $this->approved->part_number,
        118 => '', 120 => 'N',
    ];

    expect($filled)->toHaveCount(count($original))
        ->and(str_starts_with($line, '"') && str_ends_with($line, '"'))->toBeTrue();

    foreach ($original as $i => $value) {
        $column = $i + 1;
        expect($filled[$i])->toBe(array_key_exists($column, $expected) ? $expected[$column] : $value, "column {$column}");
    }

    // RFQ requirements DLA pre-filled are still there.
    expect($filled[0])->toBe('SPE1C126T1775')
        ->and($filled[23])->toBe('BI')
        ->and($filled[45])->toBe('7018426027')
        ->and($filled[46])->toBe('8465015148739')
        ->and($filled[48])->toBe('25')
        ->and($filled[104])->toBe('P');
});

test('formats prices the way DLA accepts: 0–5 decimals, at least 2 shown', function (string $in, string $out) {
    expect((new BatchQuoteExporter)->formatPrice($in))->toBe($out);
})->with([['12.5', '12.50'], ['3', '3.00'], ['0.12345', '0.12345'], ['1234567.891', '1234567.891'], ['7.10000', '7.10']]);

test('a ready quote has no problems', function () {
    expect((new BatchQuoteExporter)->problems(readyTracking($this->rfq), $this->profile))->toBe([]);
});

test('reports exactly what blocks a quote', function (array $overrides, string $problem) {
    expect(implode(' ', (new BatchQuoteExporter)->problems(readyTracking($this->rfq, $overrides), $this->profile)))->toContain($problem);
})->with([
    'no price' => [['unit_price' => null], 'Enter a unit price'],
    'zero price' => [['unit_price' => '0'], 'zero price'],
    'no delivery' => [['delivery_days' => null], 'Enter delivery days'],
    'dealer without maker CAGE' => [['manufacturing_source_cage' => null], 'CAGE of the facility'],
    'exact part not approved' => [['part_offered_number' => 'NOT-APPROVED-1'], 'must match an approved CAGE + part number'],
    'no part offered' => [['part_offered_code' => null], 'choose the part offered'],
    'alternate product' => [['part_offered_code' => '2'], 'choose the part offered'],
]);

test('blocks when the profile is not confirmed', function () {
    $this->profile->update(['confirmed_at' => null]);

    expect((new BatchQuoteExporter)->problems(readyTracking($this->rfq), $this->profile))->toContain('The quoting profile is incomplete or not confirmed.');
});

test('requires a supported higher-level quality code when DLA\'s row demands one', function () {
    $tracking = readyTracking($this->rfq);
    $template = $tracking->rfq->quoteTemplates->first();
    $cols = BatchQuoteFileParser::splitRow($template->raw_row);
    $cols[116] = '8'; // col 117: SAE AS9100 required
    $template->update(['raw_row' => '"'.implode('","', $cols).'"']);
    $tracking->load('rfq.quoteTemplates');

    $exporter = new BatchQuoteExporter;
    expect(implode(' ', $exporter->problems($tracking, $this->profile)))->toContain('higher-level quality');

    $tracking->update(['quality_code' => '7']); // ISO 9001 does not satisfy an AS9100 requirement
    expect(implode(' ', $exporter->problems($tracking, $this->profile)))->toContain('higher-level quality');

    $tracking->update(['quality_code' => '8']);
    expect($exporter->problems($tracking, $this->profile))->toBe([])
        ->and(BatchQuoteFileParser::splitRow($exporter->fill($tracking->rfq->quoteTemplates->first(), $tracking, $this->profile))[117])->toBe('8');
});

test('refuses rows DLA did not issue as a plain bid', function () {
    $tracking = readyTracking($this->rfq);
    $template = $tracking->rfq->quoteTemplates->first();
    $cols = BatchQuoteFileParser::splitRow($template->raw_row);
    $cols[23] = 'BW';
    $template->update(['raw_row' => '"'.implode('","', $cols).'"']);

    expect(implode(' ', (new BatchQuoteExporter)->problems($tracking->load('rfq.quoteTemplates'), $this->profile)))->toContain('not a plain bid');
});

test('exports Latin-1 CRLF files, marks quotes quoted and snapshots what was sent', function () {
    $tracking = readyTracking($this->rfq);

    $result = (new BatchQuoteExporter)->export(collect([$tracking]), $this->profile, $this->admin->id);

    $file = reset($result['files']);
    expect($result['files'])->toHaveCount(1)
        ->and(array_key_first($result['files']))->toEndWith('.txt')
        ->and($file)->toEndWith("\r\n")
        ->and(mb_check_encoding($file, 'UTF-8') || ! preg_match('/[\x80-\xFF]/', $file))->toBeTrue()
        ->and($result['lines'])->toBe($tracking->rfq->quoteTemplates->count());

    $tracking->refresh();
    expect($tracking->status)->toBe('quoted')
        ->and($tracking->export_reference)->toBe($result['reference'])
        ->and($tracking->quote_snapshot['lines'][0])->toContain('"12.50"')
        ->and($tracking->quote_snapshot['confirmed_by'])->toBe($this->admin->id);
});

test('splits batches over 75 lines into several files', function () {
    $template = $this->rfq->quoteTemplates()->first();
    foreach (range(1, 79) as $i) {
        DibbsQuoteTemplate::create($template->only(['solicitation_number', 'purchase_request', 'raw_row', 'dibbs_rfq_id']) + ['row_index' => $i]);
    }

    $result = (new BatchQuoteExporter)->export(collect([readyTracking($this->rfq)]), $this->profile, $this->admin->id);

    expect($result['lines'])->toBe($this->rfq->quoteTemplates()->count())
        ->and($result['lines'])->toBeGreaterThan(BatchQuoteExporter::MAX_LINES_PER_FILE)
        ->and(array_keys($result['files']))->each->toContain('-part')
        ->and(collect($result['files'])->map(fn ($f) => substr_count($f, "\r\n"))->all())->each->toBeLessThanOrEqual(BatchQuoteExporter::MAX_LINES_PER_FILE);
});

test('refuses to export a quote with problems', function () {
    (new BatchQuoteExporter)->export(collect([readyTracking($this->rfq, ['unit_price' => null])]), $this->profile, $this->admin->id);
})->throws(InvalidArgumentException::class, 'Enter a unit price');

test('starting a quote pre-fills DLA delivery days and our approved source, never the price', function () {
    Manufacturer::create(['name' => 'Approved Maker', 'cage_code' => $this->approved->cage_code]);
    (new DibbsRfqMatcher)->rebuild($this->company->id);

    $tracking = DibbsRfqTracking::create(['company_id' => $this->company->id, 'dibbs_rfq_id' => $this->rfq->id])->startQuoting();

    expect($tracking->status)->toBe('quoting')
        ->and($tracking->delivery_days)->toBe(20)
        ->and($tracking->part_offered_code)->toBe('1')
        ->and($tracking->part_offered_cage)->toBe($this->approved->cage_code)
        ->and($tracking->unit_price)->toBeNull();
});

describe('admin pages', function () {
    beforeEach(function () {
        Filament::setCurrentPanel('admin');
        $this->obLevel = ob_get_level();
        $this->actingAs($this->admin, 'admin');
    });

    afterEach(function () {
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }
    });

    test('the profile only saves with explicit confirmation, recording who confirmed', function () {
        $this->profile->delete();

        $fields = ['quoter_cage' => '9xyz8', 'quote_for_cage' => '9XYZ8', 'small_business_code' => 'B', 'affirmative_action_code' => 'NH',
            'previous_contracts_code' => 'N4', 'adr_code' => 'A', 'child_labor_code' => 'U', 'default_hazardous_material' => 'N',
            'default_material_code' => '0', 'default_manufacturer_dealer' => 'DD'];

        Livewire::test(DibbsQuotingProfilePage::class)
            ->fillForm($fields + ['confirm' => false])
            ->call('save')
            ->assertHasFormErrors(['confirm']);

        expect(DibbsQuotingProfile::count())->toBe(0);

        Livewire::test(DibbsQuotingProfilePage::class)
            ->fillForm($fields + ['confirm' => true])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSee('Confirmed');

        $profile = DibbsQuotingProfile::sole();
        expect($profile->quoter_cage)->toBe('9XYZ8')
            ->and($profile->confirmed_by)->toBe($this->admin->id)
            ->and($profile->isConfirmed())->toBeTrue();
    });

    test('the worksheet shows readiness and exports ready quotes as a download', function () {
        $ready = readyTracking($this->rfq);
        $other = DibbsRfq::where('solicitation_number', '!=', 'SPE1C126T1775')->first();
        $blocked = readyTracking($other, ['unit_price' => null]);

        Livewire::test(DibbsQuoteWorksheet::class)
            ->assertCanSeeTableRecords([$ready, $blocked])
            ->assertTableColumnStateSet('readiness', 'Ready', $ready)
            ->callTableAction('reviewAndExport', data: ['attest' => true])
            ->assertFileDownloaded();

        expect($ready->fresh()->status)->toBe('quoted')
            ->and($blocked->fresh()->status)->toBe('quoting');
    });

    test('export needs the attestation', function () {
        readyTracking($this->rfq);

        Livewire::test(DibbsQuoteWorksheet::class)
            ->callTableAction('reviewAndExport', data: ['attest' => false])
            ->assertHasTableActionErrors(['attest']);

        expect(DibbsRfqTracking::where('status', 'quoted')->count())->toBe(0);
    });

    test('edits a quote from the worksheet', function () {
        $tracking = readyTracking($this->rfq, ['unit_price' => null]);

        Livewire::test(DibbsQuoteWorksheet::class)
            ->callTableAction('editQuote', $tracking, ['unit_price' => '9.95', 'delivery_days' => 30, 'manufacturing_source_cage' => 'abcde']);

        expect((float) $tracking->fresh()->unit_price)->toBe(9.95)
            ->and($tracking->fresh()->manufacturing_source_cage)->toBe('ABCDE');
    });

    test('bulk adds RFQs to the worksheet from the RFQ list', function () {
        $records = DibbsRfq::query()->limit(2)->get();

        Livewire::test(ListDibbsRfqs::class)
            ->filterTable('our_product_codes', false)
            ->filterTable('still_open', false)
            ->callTableBulkAction('bulk_worksheet', $records);

        expect(DibbsRfqTracking::whereIn('dibbs_rfq_id', $records->pluck('id'))->pluck('status')->unique()->all())->toBe(['quoting']);
    });
});
