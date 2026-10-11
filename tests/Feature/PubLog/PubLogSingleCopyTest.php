<?php

use App\Filament\Pages\FetchNsnData;
use App\Filament\Resources\NsnSourcingCases\NsnSourcingCaseResource;
use App\Models\Admin;
use App\Models\Company;
use App\Models\NsnCatalogItem;
use App\Models\NsnReference;
use App\Models\NsnSourcingCase;
use App\Models\PubLogImport;
use App\Services\NsnLookupService;
use App\Services\NsnSourcing\NsnSourcingService;
use App\Services\PubLog\PubLogCatalog;
use App\Services\PubLog\PubLogDb;
use App\Services\PubLog\PubLogImporter;
use App\Services\PubLog\PubLogTables;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Support\PubLogFixtures;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/publog-single-'.bin2hex(random_bytes(8));
    config(['nsn_lookup.directory' => $this->directory.'/owned', 'nsn_lookup.minimum_free_bytes' => 0]);
    $this->travelTo(now()->setDate(2026, 10, 4));
    $this->catalog = app(PubLogCatalog::class);
});

afterEach(fn () => File::deleteDirectory($this->directory));

function liveRelease($test, string $name, string $release = '2026-10-01'): PubLogImport
{
    $import = PubLogFixtures::ready($test->directory.'/'.$name, $release);
    $test->catalog->activate($import->id, $test->catalog->activeId());

    return $import->fresh();
}

function rowsByImport(string $table): array
{
    return DB::table($table)->selectRaw('import_id, count(*) as n')->groupBy('import_id')->pluck('n', 'import_id')->map(fn ($n) => (int) $n)->all();
}

it('keeps exactly one copy after consecutive imports', function () {
    $one = liveRelease($this, 'one');
    $two = liveRelease($this, 'two', '2026-10-02');

    foreach (PubLogTables::DATA as $table) {
        expect(array_keys(rowsByImport($table)))->toBe($table === 'nsn_catalog_relationships' ? [] : [$two->id]);
    }
    expect(PubLogTables::anyStagingExists())->toBeFalse()
        ->and($this->catalog->activeId())->toBe($two->id)
        ->and($one->fresh()->status)->toBe('replaced')
        ->and(app(NsnLookupService::class)->lookup('nsn', '5940-00-000-0045')['status'])->toBe('match');
});

it('keeps the live copy readable while a release is staged and after it fails', function () {
    $one = liveRelease($this, 'one');
    $staged = PubLogFixtures::ready($this->directory.'/two', '2026-10-02');

    $during = app(NsnLookupService::class)->lookup('niin', '000000045');
    expect($during['status'])->toBe('match')->and($during['source']['import_id'])->toBe($one->id)
        ->and(rowsByImport(PubLogTables::staging('nsn_catalog_items')))->toBe([$staged->id => 5]);

    $service = app(PubLogImporter::class);
    $bad = $service->prepare(PubLogFixtures::archives($this->directory.'/bad', function ($files) {
        $files['V_FLIS_PART.CSV'] .= '"000000042","unterminated'."\r\n";

        return $files;
    }), '2026-10-03');
    expect(fn () => $service->run($bad))->toThrow(RuntimeException::class, 'Unterminated');

    expect($staged->fresh()->status)->toBe('abandoned')
        ->and(is_dir($staged->manifest['directory']))->toBeFalse()
        ->and($bad->fresh()->status)->toBe('failed')
        ->and($this->catalog->activeId())->toBe($one->id)
        ->and(rowsByImport('nsn_catalog_items'))->toBe([$one->id => 5])
        ->and(app(NsnLookupService::class)->lookup('nsn', '5940-00-000-0045')['status'])->toBe('match')
        ->and(fn () => $this->catalog->activate($staged->id, $one->id))->toThrow(RuntimeException::class, 'validated');
});

it('restarts an import whose staging tables were lost', function () {
    $service = app(PubLogImporter::class);
    $import = $service->prepare(PubLogFixtures::archives($this->directory.'/source'), '2026-10-01');
    expect(fn () => $service->run($import, report: function ($member, $progress) {
        if ($member === 'V_FLIS_PART.CSV') {
            throw new RuntimeException('Simulated crash');
        }
    }))->toThrow(RuntimeException::class, 'Simulated');
    PubLogTables::dropStaging();

    $result = $service->run($import->fresh());
    expect($result->status)->toBe('ready')->and(rowsByImport(PubLogTables::staging('nsn_catalog_items')))->toBe([$import->id => 5]);
    $this->catalog->activate($result->id, null);
    expect(NsnCatalogItem::count())->toBe(5);
});

it('re-imports a release whose copy was replaced but not the live one', function () {
    $source = PubLogFixtures::archives($this->directory.'/one');
    $service = app(PubLogImporter::class);
    $one = $service->run($service->prepare($source, '2026-10-01'));
    $this->catalog->activate($one->id, null);
    expect($service->prepare($source, '2026-10-01')->id)->toBe($one->id);

    $two = liveRelease($this, 'two', '2026-10-02');
    $again = $service->run($service->prepare($source, '2026-10-01'));
    expect($again->id)->not->toBe($one->id)->and($again->status)->toBe('ready')
        ->and(fn () => $this->catalog->activate($again->id, $two->id))->toThrow(RuntimeException::class, 'older');
});

it('does not import the history table unless enabled', function () {
    $import = liveRelease($this, 'one');

    expect(DB::table('nsn_catalog_relationships')->count())->toBe(0)
        ->and($import->validation['counts'])->not->toHaveKey('nsn_catalog_relationships')
        ->and(app(NsnLookupService::class)->lookup('niin', '000000012')['relationships'])->toBe([]);
    $this->artisan('publog:status', ['--json' => true])->assertSuccessful()->expectsOutputToContain('"history_imported": false');
});

it('creates staging tables with the same columns as the live tables', function () {
    PubLogTables::createStaging(99);
    foreach (PubLogTables::DATA as $table) {
        expect(Schema::getColumnListing(PubLogTables::staging($table)))->toBe(Schema::getColumnListing($table));
    }
    PubLogTables::dropStaging();
    expect(PubLogTables::anyStagingExists())->toBeFalse();
});

it('finishes an activation that was interrupted after the swap', function () {
    $one = liveRelease($this, 'one');
    $two = PubLogFixtures::ready($this->directory.'/two', '2026-10-02');
    PubLogTables::swap();
    expect($this->catalog->activeId())->toBe($one->id);

    $this->catalog->activate($two->id, $one->id);
    expect($this->catalog->activeId())->toBe($two->id)->and(rowsByImport('nsn_catalog_items'))->toBe([$two->id => 5]);
});

it('keeps sourcing cases working after their release is replaced', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Company::factory()->create();
    $this->actingAs(Admin::factory()->create(['has_email_authentication' => true]), 'admin');
    liveRelease($this, 'one');
    $case = app(NsnSourcingService::class)->openFromReference(NsnReference::where('niin', '000000045')->where('cage_code', '24039')->value('id'));

    liveRelease($this, 'two', '2026-10-02');
    $newReference = NsnReference::where('niin', '000000045')->where('cage_code', '24039')->first();

    expect(app(NsnSourcingService::class)->openFromReference($newReference->id)->id)->toBe($case->id)
        ->and(NsnSourcingCase::count())->toBe(1);
    $this->get(NsnSourcingCaseResource::getUrl('view', ['record' => $case]))->assertOk()->assertSee('Since replaced by a newer release');
    Livewire::test(FetchNsnData::class)->fillForm(['type' => 'niin', 'query' => '000000045'])->call('fetchNsnData')->assertSee('Open sourcing case');
});

it('retires rollback and reports staging in status', function () {
    $one = liveRelease($this, 'one');
    $this->artisan('publog:activate', ['import' => $one->id, '--expected-current' => (string) $one->id, '--rollback' => true])
        ->expectsOutputToContain('Rollback is not available')->assertFailed();
    PubLogFixtures::ready($this->directory.'/two', '2026-10-02');
    $this->artisan('publog:status', ['--json' => true])->assertSuccessful()->expectsOutputToContain('"staging_tables": true');
});

it('rebuilds the live release from the same files on request', function () {
    $source = PubLogFixtures::archives($this->directory.'/one');
    $this->artisan('publog:import', ['directory' => $source, '--release' => '2026-10-01', '--activate' => true])->assertSuccessful();
    $first = PubLogImport::sole();
    $this->artisan('publog:import', ['directory' => $source, '--release' => '2026-10-01', '--reimport' => true, '--activate' => true])->assertSuccessful();

    $rebuilt = PubLogImport::latest('id')->first();
    expect($rebuilt->id)->not->toBe($first->id)
        ->and($this->catalog->activeId())->toBe($rebuilt->id)
        ->and($first->fresh()->status)->toBe('replaced')
        ->and(rowsByImport('nsn_catalog_items'))->toBe([$rebuilt->id => 5]);
});

it('discards a staged release without touching the live copy', function () {
    $one = liveRelease($this, 'one');
    $staged = PubLogFixtures::ready($this->directory.'/two', '2026-10-02');

    $this->artisan('publog:discard-staging', ['--force' => true])->assertSuccessful();

    expect(PubLogTables::anyStagingExists())->toBeFalse()
        ->and($staged->fresh()->status)->toBe('abandoned')
        ->and($this->catalog->activeId())->toBe($one->id)
        ->and(rowsByImport('nsn_catalog_items'))->toBe([$one->id => 5]);
    $this->artisan('publog:discard-staging', ['--force' => true])->expectsOutputToContain('Nothing is staged')->assertSuccessful();
});

it('only touches the binary log setting on MySQL when asked', function () {
    config(['nsn_lookup.skip_binlog' => true]);
    PubLogDb::prepareForBulkWrite();
    expect(liveRelease($this, 'one')->status)->toBe('ready');
});
