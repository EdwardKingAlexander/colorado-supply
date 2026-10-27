<?php

use App\Filament\Resources\DibbsRfqResource\Pages\ListDibbsRfqs;
use App\Filament\Resources\DibbsRfqResource\Pages\ViewDibbsRfq;
use App\Filament\Resources\MilSpecParts\MilSpecPartResource;
use App\Filament\Resources\MilSpecParts\Pages\ImportMilSpecParts;
use App\Jobs\RebuildDibbsMatchesJob;
use App\Models\Admin;
use App\Models\Company;
use App\Models\DibbsApprovedSource;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqMatch;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Models\ProcurementHistory;
use App\Models\Supplier;
use App\Services\Catalog\MilSpecPartCatalogImporter;
use App\Services\Dibbs\DibbsRfqMatcher;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Company::factory()->create();
    $this->screwRfq = DibbsRfq::factory()->create(['nsn' => '5305012345678', 'fsc' => '5305', 'nomenclature' => 'SCREW,CAP', 'return_by_date' => today()->addDays(5)]);
    $this->valveRfq = DibbsRfq::factory()->create(['nsn' => '4820015550101', 'fsc' => '4820', 'nomenclature' => 'VALVE,GATE', 'amsc' => 'C', 'return_by_date' => today()->addDays(6)]);
    $this->otherRfq = DibbsRfq::factory()->create(['nsn' => '5310099990000', 'fsc' => '5310', 'return_by_date' => today()->addDays(7)]);
});

function catalogRows(array ...$rows): array
{
    return array_map(fn ($r) => array_combine(MilSpecPartCatalogImporter::COLUMNS, array_pad($r, 8, null)), $rows);
}

describe('catalog importer', function () {
    test('previews new and updated parts without saving', function () {
        $maker = Manufacturer::create(['name' => 'Example Fastener Co', 'cage_code' => '1ABC2']);
        MilSpecPart::create(['nsn' => '5305-01-234-5678', 'description' => 'old', 'manufacturer_id' => $maker->id]);

        $plan = (new MilSpecPartCatalogImporter)->analyze(catalogRows(
            ['5305-01-234-5678', 'SCREW,CAP', null, '1abc2', 'HCS-1'],
            ['4820015550101', 'VALVE,GATE', 'Example Valve Co', '79227', 'GV-200', 'Example Supply', '9XYZ8', 'ES-1'],
        ));

        expect($plan)->toMatchArray(['new_parts' => 1, 'updated_parts' => 1, 'new_manufacturers' => 1, 'new_suppliers' => 1, 'is_valid' => true])
            ->and(MilSpecPart::count())->toBe(1);
    });

    test('reports row problems with row numbers', function () {
        $plan = (new MilSpecPartCatalogImporter)->analyze(catalogRows(
            ['530501234567', 'SHORT NSN', 'Maker'],
            ['5305012345678', null, 'Maker'],
            ['4820015550101', 'VALVE', 'Maker', 'TOOLONG'],
            ['5310099990000', 'NUT', null, 'ZZZZZ'],
            ['6145011112222', 'WIRE', 'Maker'],
            ['6145-01-111-2222', 'WIRE AGAIN', 'Maker'],
        ));

        expect($plan['is_valid'])->toBeFalse()
            ->and(collect($plan['errors'])->pluck('row')->all())->toBe([2, 3, 4, 5, 7])
            ->and($plan['errors'][0]['message'])->toContain('4-digit FSC')
            ->and($plan['errors'][3]['message'])->toContain('Manufacturer name is required')
            ->and($plan['errors'][4]['message'])->toContain('more than once');
    });

    test('commits in the catalog\'s dashed format, matching manufacturers by CAGE and linking suppliers', function () {
        $maker = Manufacturer::create(['name' => 'Fasteners Inc', 'cage_code' => '1ABC2']);
        Queue::fake(); // after setup: creating that manufacturer rightly queues a rebuild
        $importer = new MilSpecPartCatalogImporter;

        $result = $importer->commit($importer->analyze(catalogRows(
            ['5305012345678', 'SCREW,CAP', 'Some Other Name', '1ABC2', 'HCS-1', 'Example Supply', '9XYZ8', 'ES-55'],
        )));

        $part = MilSpecPart::sole();

        expect($result['created'])->toBe(1)
            ->and($part->nsn)->toBe('5305-01-234-5678')
            ->and($part->manufacturer_id)->toBe($maker->id)
            ->and(Manufacturer::count())->toBe(1)
            ->and($part->suppliers->sole()->pivot->supplier_part_number)->toBe('ES-55')
            ->and($result['matches']['nsn_catalog'])->toBe(1);

        // One rebuild at the end, not one per saved row.
        Queue::assertNotPushed(RebuildDibbsMatchesJob::class);
    });

    test('refuses to commit a plan with errors', function () {
        $importer = new MilSpecPartCatalogImporter;

        $importer->commit($importer->analyze(catalogRows(['bad', 'X', 'Maker'])));
    })->throws(InvalidArgumentException::class);
});

describe('matcher', function () {
    test('matches catalog NSNs stored with dashes and approved sources by our manufacturer and supplier CAGEs', function () {
        $maker = Manufacturer::create(['name' => 'Example Valve Co', 'cage_code' => '79227']);
        $supplier = Supplier::create(['name' => 'Example Supply', 'cage_code' => '9xyz8']);
        RebuildDibbsMatchesJob::withoutRebuilds(fn () => MilSpecPart::create(['nsn' => '5305-01-234-5678', 'description' => 'SCREW', 'manufacturer_id' => $maker->id]));
        DibbsApprovedSource::create(['nsn' => '4820015550101', 'cage_code' => '79227', 'part_number' => 'GV-200']);
        DibbsApprovedSource::create(['nsn' => '4820015550101', 'cage_code' => '9XYZ8', 'part_number' => 'GV-200-S']);
        DibbsApprovedSource::create(['nsn' => '5310099990000', 'cage_code' => '00000', 'part_number' => 'N-1']);

        $counts = (new DibbsRfqMatcher)->rebuild();

        expect($counts)->toBe(['nsn_catalog' => 1, 'approved_source' => 2])
            ->and($this->screwRfq->matches->sole()->reason)->toBe(DibbsRfqMatch::REASON_CATALOG)
            ->and($this->valveRfq->matches->pluck('matchable_type')->sort()->values()->all())->toBe([Manufacturer::class, Supplier::class])
            ->and($this->valveRfq->matches->firstWhere('matchable_type', Manufacturer::class)->part_number)->toBe('GV-200')
            ->and($this->otherRfq->matches)->toBeEmpty();
    });

    test('is idempotent', function () {
        $maker = Manufacturer::create(['name' => 'M', 'cage_code' => '79227']);
        DibbsApprovedSource::create(['nsn' => '4820015550101', 'cage_code' => '79227', 'part_number' => 'GV-200']);

        (new DibbsRfqMatcher)->rebuild();
        (new DibbsRfqMatcher)->rebuild();

        expect(DibbsRfqMatch::count())->toBe(1);
    });

    test('catalog changes that affect matching queue a rebuild; others do not', function () {
        Queue::fake();

        $maker = Manufacturer::create(['name' => 'No CAGE yet']);
        Queue::assertNotPushed(RebuildDibbsMatchesJob::class);

        $maker->update(['name' => 'Renamed']);
        Queue::assertNotPushed(RebuildDibbsMatchesJob::class);

        $maker->update(['cage_code' => '79227']);
        Queue::assertPushed(RebuildDibbsMatchesJob::class);
    });
});

describe('admin UI', function () {
    beforeEach(function () {
        Filament::setCurrentPanel('admin');
        $this->obLevel = ob_get_level();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->maker = Manufacturer::create(['name' => 'Example Valve Co', 'cage_code' => '79227']);
        DibbsApprovedSource::create(['nsn' => '4820015550101', 'cage_code' => '79227', 'part_number' => 'GV-200', 'company_name' => 'EXAMPLE VALVE']);
        DibbsApprovedSource::create(['nsn' => '4820015550101', 'cage_code' => '11111', 'part_number' => 'OTHER-1', 'company_name' => 'SOMEONE ELSE']);
        $part = RebuildDibbsMatchesJob::withoutRebuilds(fn () => MilSpecPart::create(['nsn' => '5305-01-234-5678', 'description' => 'SCREW,CAP,HEX', 'manufacturer_id' => $this->maker->id, 'manufacturer_part_number' => 'HCS-1']));
        $supplier = Supplier::create(['name' => 'Example Supply']);
        $part->suppliers()->attach($supplier->id, ['supplier_part_number' => 'ES-55']);
        ProcurementHistory::create(['mil_spec_part_id' => $part->id, 'supplier_id' => $supplier->id, 'price' => 1.25, 'quantity' => 500, 'acquisition_date' => '2026-08-01', 'source_url' => 'https://example.test/po/1']);
        (new DibbsRfqMatcher)->rebuild();
    });

    afterEach(function () {
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }
    });

    test('the RFQ list shows match badges and filters to matched RFQs', function () {
        Livewire::test(ListDibbsRfqs::class)
            ->assertTableColumnStateSet('match', DibbsRfqMatch::REASON_CATALOG, $this->screwRfq)
            ->assertTableColumnStateSet('match', DibbsRfqMatch::REASON_APPROVED_SOURCE, $this->valveRfq)
            ->filterTable('match', 'any')
            ->assertCanSeeTableRecords([$this->screwRfq, $this->valveRfq])
            ->assertCanNotSeeTableRecords([$this->otherRfq])
            ->filterTable('match', DibbsRfqMatch::REASON_CATALOG)
            ->assertCanSeeTableRecords([$this->screwRfq])
            ->assertCanNotSeeTableRecords([$this->valveRfq]);
    });

    test('the detail page shows our catalog part, suppliers and last price', function () {
        Livewire::test(ViewDibbsRfq::class, ['record' => $this->screwRfq->getRouteKey()])
            ->assertSee('Your catalog')
            ->assertSee('SCREW,CAP,HEX')
            ->assertSee('Example Valve Co (79227) · P/N HCS-1')
            ->assertSee('Example Supply (ES-55)')
            ->assertSee('$1.25 × 500 on Aug 1, 2026 from Example Supply')
            ->assertActionHidden('addToCatalog');
    });

    test('approved sources we buy from are marked', function () {
        Livewire::test(ViewDibbsRfq::class, ['record' => $this->valveRfq->getRouteKey()])
            ->assertDontSee('Your catalog')
            ->assertSee('✓ 79227')
            ->assertSee('Example Valve Co (manufacturer)')
            ->assertSee('11111');
    });

    test('adds an RFQ\'s NSN to the catalog and matches it immediately', function () {
        Livewire::test(ViewDibbsRfq::class, ['record' => $this->otherRfq->getRouteKey()])
            ->callAction('addToCatalog', ['description' => 'NUT,PLAIN', 'manufacturer_id' => $this->maker->id, 'manufacturer_part_number' => 'N-9'])
            ->assertNotified('Added to catalog');

        expect(MilSpecPart::where('nsn', '5310-09-999-0000')->exists())->toBeTrue()
            ->and($this->otherRfq->fresh()->matches->pluck('reason')->all())->toBe([DibbsRfqMatch::REASON_CATALOG]);
    });

    test('imports a catalog file through preview and confirm', function () {
        $csv = implode("\n", array_map(fn ($r) => implode(',', $r), [
            MilSpecPartCatalogImporter::COLUMNS,
            ['4820-01-555-0101', 'VALVE GATE', 'Example Valve Co', '79227', 'GV-200', 'Example Supply', '', 'ES-77'],
        ]));

        // Same seam as CategoryImportTest: point the page at a real file.
        $path = tempnam(sys_get_temp_dir(), 'catalog').'.csv';
        file_put_contents($path, $csv);

        Livewire::test(ImportMilSpecParts::class)
            ->set('uploadedAbsolutePath', $path)
            ->call('analyze')
            ->assertSet('preview.new_parts', 1)
            ->assertSet('preview.is_valid', true)
            ->call('confirm')
            ->assertRedirect(MilSpecPartResource::getUrl('index'));

        @unlink($path);

        expect(MilSpecPart::where('nsn', '4820-01-555-0101')->exists())->toBeTrue()
            ->and($this->valveRfq->fresh()->matches->pluck('reason')->sort()->values()->all())->toBe([DibbsRfqMatch::REASON_APPROVED_SOURCE, DibbsRfqMatch::REASON_CATALOG]);
    });
});
