<?php

use App\Filament\Pages\DibbsApprovedSourcesReport;
use App\Models\Admin;
use App\Models\Company;
use App\Models\DibbsApprovedSource;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqMatch;
use App\Models\Manufacturer;
use App\Models\SamCageLookup;
use App\Services\Sam\CageDirectory;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Company::factory()->create();
    config(['services.sam.api_key' => 'test-key', 'services.sam.entity_url' => 'https://sam.test/entity-information/v3/entities']);
    Http::preventStrayRequests();

    // Two open RFQs approve 56878 (fasteners), one approves 07TA6, a closed one approves 99999.
    foreach ([['5305000000001', '56878'], ['5305000000002', '56878'], ['6515000000003', '07TA6']] as [$nsn, $cage]) {
        DibbsRfq::factory()->create(['nsn' => $nsn, 'fsc' => substr($nsn, 0, 4), 'return_by_date' => today()->addDays(5)]);
        DibbsApprovedSource::create(['nsn' => $nsn, 'cage_code' => $cage, 'part_number' => "PN-{$nsn}"]);
    }
    DibbsRfq::factory()->create(['nsn' => '5305000000009', 'fsc' => '5305', 'return_by_date' => today()->subDay()]);
    DibbsApprovedSource::create(['nsn' => '5305000000009', 'cage_code' => '99999', 'part_number' => 'OLD']);
});

function fakeEntityApi(array $entities, int $status = 200): void
{
    Http::fake(['sam.test/*' => Http::response($status === 200 ? [
        'totalRecords' => count($entities),
        'entityData' => array_map(fn ($e) => [
            'entityRegistration' => ['cageCode' => $e[0], 'legalBusinessName' => $e[1], 'registrationStatus' => 'Active', 'ueiSAM' => 'UEI'.$e[0]],
            'coreData' => ['physicalAddress' => ['city' => $e[2], 'stateOrProvinceCode' => $e[3], 'countryCode' => 'USA']],
        ], $entities),
    ] : ['error' => 'limit'], $status)]);
}

describe('CAGE directory', function () {
    test('looks codes up in one request joined with ~ and caches them, including not-found codes', function () {
        fakeEntityApi([['56878', 'SPS TECHNOLOGIES, LLC', 'JENKINTOWN', 'PA']]);

        $result = (new CageDirectory)->lookup(['56878', '0zzz9']);

        expect($result)->toBe(['requested' => 2, 'requests' => 1, 'found' => 1]);
        Http::assertSent(fn (Request $r) => $r['cageCode'] === '56878~0ZZZ9' && $r['api_key'] === 'test-key');

        expect(SamCageLookup::where('cage_code', '56878')->sole()->displayName())->toBe('SPS TECHNOLOGIES, LLC')
            ->and(SamCageLookup::where('cage_code', '0ZZZ9')->sole()->found)->toBeFalse();

        // Cached: no second request.
        expect((new CageDirectory)->lookup(['56878', '0ZZZ9'])['requests'])->toBe(0);
        Http::assertSentCount(1);
    });

    test('reads every page: SAM returns 10 entities per page', function () {
        // 15 matching companies: page 0 has 10, page 1 has 5 (real API behaviour, verified 2026-10-04).
        $all = array_map(fn ($i) => [sprintf('1%04d', $i), "COMPANY {$i}", 'CITY', 'CO'], range(1, 15));
        Http::fake(fn (Request $r) => Http::response([
            'totalRecords' => 15,
            'entityData' => array_map(fn ($e) => ['entityRegistration' => ['cageCode' => $e[0], 'legalBusinessName' => $e[1]], 'coreData' => []],
                array_slice($all, (int) $r['page'] * 10, 10)),
        ]));

        $result = (new CageDirectory)->lookup(array_column($all, 0));

        expect($result)->toMatchArray(['requests' => 2, 'found' => 15])
            ->and(SamCageLookup::where('found', true)->count())->toBe(15);
    });

    test('a limit hit mid-way saves what was found and does not mark the rest not-found', function () {
        $first = array_map(fn ($i) => [sprintf('2%04d', $i), "COMPANY {$i}"], range(1, 10));
        Http::fake(fn (Request $r) => (int) $r['page'] === 0
            ? Http::response(['totalRecords' => 15, 'entityData' => array_map(fn ($e) => ['entityRegistration' => ['cageCode' => $e[0], 'legalBusinessName' => $e[1]], 'coreData' => []], $first)])
            : Http::response(['error' => 'limit'], 429));

        $cages = [...array_column($first, 0), '29991', '29992', '29993', '29994', '29995'];

        expect(fn () => (new CageDirectory)->lookup($cages))->toThrow(RuntimeException::class, '10 found so far were saved');
        expect(SamCageLookup::where('found', true)->count())->toBe(10)
            ->and(SamCageLookup::where('found', false)->count())->toBe(0);
    });

    test('batches at 100 codes per request', function () {
        fakeEntityApi([]);

        $cages = array_map(fn ($i) => sprintf('%05d', $i), range(1, 150));
        expect((new CageDirectory)->lookup($cages)['requests'])->toBe(2);
    });

    test('explains the daily limit', function () {
        fakeEntityApi([], 429);

        (new CageDirectory)->lookup(['56878']);
    })->throws(RuntimeException::class, '10 requests/day');
});

describe('approved sources page', function () {
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

    test('ranks approved companies by open RFQs in our product codes, ignoring closed RFQs', function () {
        $rows = (new DibbsApprovedSourcesReport)->rows();

        expect($rows->pluck('cage_code')->all())->toBe(['56878', '07TA6'])
            ->and($rows->first())->toMatchArray(['rank' => 1, 'open_rfqs' => 2, 'company' => 'Name not looked up yet', 'ours' => null]);
    });

    test('looks up names and shows them', function () {
        fakeEntityApi([['56878', 'SPS TECHNOLOGIES, LLC', 'JENKINTOWN', 'PA'], ['07TA6', 'CARDINAL HEALTH 200, LLC', 'WAUKEGAN', 'IL']]);

        Livewire::test(DibbsApprovedSourcesReport::class)
            ->callTableAction('lookupNames')
            ->assertNotified('Looked up 2 companies')
            ->assertSee('SPS TECHNOLOGIES, LLC')
            ->assertSee('JENKINTOWN, PA');
    });

    test('adding a company as a manufacturer turns on approved-source matches for its RFQs', function () {
        SamCageLookup::create(['cage_code' => '56878', 'found' => true, 'legal_business_name' => 'SPS TECHNOLOGIES, LLC', 'fetched_at' => now()]);

        Livewire::test(DibbsApprovedSourcesReport::class)
            ->callTableAction('addManufacturer', '56878')
            ->assertNotified('Added SPS TECHNOLOGIES, LLC');

        expect(Manufacturer::where('cage_code', '56878')->sole()->name)->toBe('SPS TECHNOLOGIES, LLC')
            ->and(DibbsRfqMatch::where('reason', DibbsRfqMatch::REASON_APPROVED_SOURCE)->count())->toBe(2)
            ->and((new DibbsApprovedSourcesReport)->rows()->first()['ours'])->toBe('Manufacturer');
    });
});
