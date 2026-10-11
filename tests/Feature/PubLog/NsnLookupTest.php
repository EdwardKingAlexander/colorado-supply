<?php

use App\Filament\Pages\FetchNsnData;
use App\Filament\Resources\MilSpecParts\Pages\CreateMilSpecPart;
use App\Jobs\FetchNsnDataJob;
use App\Mcp\Servers\Business\Tools\FetchNsnDataTool;
use App\Models\Admin;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Models\NsnReference;
use App\Models\PubLogImport;
use App\Models\User;
use App\Services\NsnLookupService;
use App\Services\PubLog\PubLogCatalog;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\PubLogFixtures;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/nsn-interface-'.bin2hex(random_bytes(8));
    config(['nsn_lookup.directory' => $this->directory.'/owned', 'nsn_lookup.minimum_free_bytes' => 0]);
    $this->travelTo(now()->setDate(2026, 10, 9));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->lookup = app(NsnLookupService::class);
});
afterEach(function () {
    File::deleteDirectory($this->directory);
});

function activateLookupFixture($test): PubLogImport
{
    $import = PubLogFixtures::ready($test->directory.'/source');
    app(PubLogCatalog::class)->activate($import->id, null);

    return $import;
}

it('validates input strictly without losing leading zeros or part punctuation', function () {
    foreach ([['nsn', 'x5940000000045'], ['niin', '123'], ['cage', '`Q9C4'], ['cage', 'A 123'], ['unknown', 'foo'], ['part', "x\ny"]] as [$type,$value]) {
        expect($this->lookup->lookup($type, $value)['status'])->toBe('invalid_input');
    }
    expect($this->lookup->normalize('nsn', '5940-00-000-0045'))->toBe('5940000000045')
        ->and($this->lookup->normalize('niin', '00-000-0045'))->toBe('000000045')
        ->and($this->lookup->normalize('cage', '0cen3'))->toBe('0CEN3')
        ->and($this->lookup->normalize('part', ' bhma-a156.4 '))->toBe('BHMA-A156.4');
});

it('distinguishes catalog unavailable from no match and operational failure', function () {
    expect($this->lookup->lookup('nsn', '5940000000045')['status'])->toBe('catalog_unavailable');
    activateLookupFixture($this);
    expect($this->lookup->lookup('niin', '999999999')['status'])->toBe('no_match');
    $this->mock(PubLogCatalog::class)->shouldReceive('snapshot')->andThrow(new RuntimeException('private database details'));
    $failed = $this->lookup->lookup('niin', '000000045');
    expect($failed['status'])->toBe('operational_failure')->and(json_encode($failed))->not->toContain('private database details');
});

it('returns all reference identities and definitions with no curated writes', function () {
    activateLookupFixture($this);
    $nsn = $this->lookup->lookup('nsn', '5940-00-000-0045');
    $niin = $this->lookup->lookup('niin', '000000045');
    expect($nsn['status'])->toBe('match')->and($nsn['references'])->toHaveCount(2)
        ->and($nsn['references'])->toBe($niin['references'])
        ->and($nsn['references'][0]['code_meanings']['RNCC'])->not->toBeEmpty()
        ->and(MilSpecPart::count())->toBe(0)->and(Manufacturer::count())->toBe(0);
    $ambiguous = $this->lookup->lookup('part', 'bhma-a156.4');
    expect($ambiguous['items'])->toHaveCount(2)
        ->and($this->lookup->lookup('part', 'BHMAA1564')['status'])->toBe('no_match')
        ->and($this->lookup->lookup('cage', '0cen3')['companies'][0]['cage_code'])->toBe('0CEN3');
});

it('paginates every reference and stays on one release after activation changes', function () {
    $import = activateLookupFixture($this);
    $template = NsnReference::where('niin', '000000045')->first()->getRawOriginal();
    unset($template['id']);
    for ($i = 0; $i < 23; $i++) {
        DB::table('nsn_references')->insert([...$template, 'part_number' => 'TEST-'.$i, 'part_search_key' => 'TEST-'.$i, 'row_hash' => hash('sha256', 'test-'.$i, true)]);
    }
    $first = $this->lookup->lookup('niin', '000000045');
    expect($first['references'])->toHaveCount(20)->and($first['has_more'])->toBeTrue();
    DB::table('pub_log_catalog_state')->where('id', 1)->update(['active_import_id' => null]);
    $second = $this->lookup->lookup('niin', '000000045', 2, $import->id);
    expect($second['references'])->toHaveCount(5)->and($second['has_more'])->toBeFalse()
        ->and(array_intersect(array_column($first['references'], 'id'), array_column($second['references'], 'id')))->toBeEmpty();
    $import->refresh()->update(['activated_at' => null]);
    expect($this->lookup->lookup('niin', '000000045', 1, $import->id)['status'])->toBe('catalog_unavailable');
});

it('flags stale releases unresolved companies and nonstandard source CAGE values', function () {
    $import = activateLookupFixture($this);
    $import->update(['release_date' => '2026-01-01']);
    $reference = NsnReference::where('niin', '000000045')->first();
    $reference->update(['cage_code' => '`Q9C4']);
    $this->actingAs(Admin::factory()->create(), 'admin');
    Livewire::test(FetchNsnData::class)->fillForm(['type' => 'niin', 'query' => '000000045'])
        ->call('fetchNsnData')->assertSet('result.source.stale', true)
        ->assertSee('Source anomaly: invalid CAGE format')->assertSee('Company identity is unresolved');
    expect($this->lookup->lookup('niin', '000000045')['references'][0]['cage_code_valid'])->toBeFalse();
});

it('renders an admin search with safe content and supports switching to all item references', function () {
    activateLookupFixture($this);
    $this->actingAs(Admin::factory()->create(['has_email_authentication' => true]), 'admin');
    $this->get(FetchNsnData::getUrl(panel: 'admin'))->assertOk()->assertSee('Search PUB LOG');
    DB::table('nsn_catalog_items')->where('niin', '000000045')->update(['item_name' => '<script>alert(1)</script>']);
    Livewire::test(FetchNsnData::class)->fillForm(['type' => 'cage', 'query' => '0cen3'])->call('fetchNsnData')
        ->assertSee('Open CAGE Search')->assertDontSee('@js(', escape: false)->assertSee("code: '0CEN3'", escape: false)->assertDontSee('<script>alert(1)</script>', escape: false)
        ->call('inspectItem', '000000045')->assertSet('result.type', 'niin')->assertSet('result.query', '000000045')
        ->assertSee('UK 60A890D255');
    expect(MilSpecPart::count())->toBe(0);
});

it('denies customer access and keeps the search snapshot locked', function () {
    $this->actingAs(User::factory()->create(), 'web');
    Livewire::test(FetchNsnData::class)->assertForbidden();
    $this->actingAs(Admin::factory()->create(), 'admin');
    expect(fn () => Livewire::test(FetchNsnData::class)->set('result', ['status' => 'match']))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('prefills a draft only and requires deliberate manufacturer selection before saving', function () {
    Queue::fake();
    activateLookupFixture($this);
    $reference = NsnReference::where('niin', '000000045')->first();
    $this->actingAs(Admin::factory()->create(), 'admin');
    $component = Livewire::withQueryParams(['catalog_reference' => (string) $reference->id])->test(CreateMilSpecPart::class)
        ->assertFormSet(['nsn' => '5940-00-000-0045', 'manufacturer_part_number' => $reference->part_number, 'manufacturer_id' => null]);
    expect(MilSpecPart::count())->toBe(0)->and(Manufacturer::count())->toBe(0);
    $component->call('create')->assertHasFormErrors(['manufacturer_id' => 'required']);
    $manufacturer = Manufacturer::create(['name' => 'Verified company', 'cage_code' => '24039']);
    $component->fillForm(['manufacturer_id' => $manufacturer->id])->call('create')->assertHasNoFormErrors();
    expect(MilSpecPart::count())->toBe(1)->and(MilSpecPart::first()->manufacturer_id)->toBe($manufacturer->id);
});

it('preserves an existing curated record even if its NSN has different formatting', function () {
    Queue::fake();
    activateLookupFixture($this);
    $manufacturer = Manufacturer::create(['name' => 'Existing company', 'cage_code' => '24039']);
    $part = MilSpecPart::create(['nsn' => '5940000000045', 'description' => 'Keep me', 'manufacturer_id' => $manufacturer->id, 'manufacturer_part_number' => 'PREFERRED']);
    $reference = NsnReference::where('niin', '000000045')->first();
    $this->actingAs(Admin::factory()->create(), 'admin');
    Livewire::withQueryParams(['catalog_reference' => (string) $reference->id])->test(CreateMilSpecPart::class)
        ->fillForm(['manufacturer_id' => $manufacturer->id])->call('create')->assertHasFormErrors(['nsn']);
    expect($part->fresh()->description)->toBe('Keep me')->and(MilSpecPart::count())->toBe(1)
        ->and($this->lookup->lookup('niin', '000000045')['items'][0]['curated_id'])->toBe($part->id);
});

it('supports legacy MCP NSN inputs with truthful read-only output and retires old queued writes', function () {
    activateLookupFixture($this);
    $tool = app(FetchNsnDataTool::class);
    $result = json_decode($tool->execute(['nsn' => '5940000000045']), true);
    expect($result['success'])->toBeTrue()->and($result['saved'])->toBeFalse()->and($result['mil_spec_part_id'])->toBeNull()
        ->and(json_decode($tool->execute(['query' => []]), true)['status'])->toBe('invalid_input');
    expect(fn () => (new FetchNsnDataJob('5940000000045'))->handle())->toThrow(RuntimeException::class, 'retired');
    expect(MilSpecPart::count())->toBe(0);
});

it('paginates status context separately and preserves the searched value while moving pages', function () {
    config(['nsn_lookup.import_relationships' => true]);
    $import = activateLookupFixture($this);
    $template = DB::table('nsn_catalog_relationships')->where('niin', '000000012')->first();
    $row = (array) $template;
    unset($row['id']);
    for ($i = 0; $i < 22; $i++) {
        DB::table('nsn_catalog_relationships')->insert([...$row, 'row_hash' => hash('sha256', 'status-'.$i, true)]);
    }
    $first = $this->lookup->lookup('niin', '000000012');
    $next = $this->lookup->lookup('niin', '000000012', snapshotId: $import->id, relationshipPage: 2);
    expect($first['relationships'])->toHaveCount(20)
        ->and($first['relationships_have_more'])->toBeTrue()
        ->and($next['relationships'])->not->toBeEmpty()
        ->and($next['relationships_have_more'])->toBeFalse();
    $this->actingAs(Admin::factory()->create(), 'admin');
    Livewire::test(FetchNsnData::class)->fillForm(['type' => 'niin', 'query' => '000000012'])
        ->call('fetchNsnData')->fillForm(['type' => 'part', 'query' => 'UNSUBMITTED'])
        ->call('changePage', 1, true)->assertSet('result.query', '000000012')
        ->assertSet('result.relationship_page', 2)->assertSet('result.source.import_id', $import->id);
});

it('rejects curated prefill from a release that was never activated', function () {
    PubLogFixtures::ready($this->directory.'/source');
    // A staged (never activated) release is not visible to the app.
    $reference = (object) ['id' => DB::table('nsn_references_next')->value('id')];
    $this->actingAs(Admin::factory()->create(), 'admin');
    Livewire::withQueryParams(['catalog_reference' => (string) $reference->id])->test(CreateMilSpecPart::class)->assertNotFound();
    expect(MilSpecPart::count())->toBe(0);
});

it('publishes the MCP search schema for all supported input types', function () {
    $schema = app(FetchNsnDataTool::class)->toArray()['inputSchema'];
    expect($schema['properties']['type']['enum'])->toBe(['nsn', 'niin', 'part', 'cage'])
        ->and($schema['properties'])->toHaveKeys(['query', 'nsn', 'page', 'snapshot_id', 'relationship_page']);
});

it('discards partial results if a later query fails', function () {
    activateLookupFixture($this);
    DB::listen(function ($query) {
        if (str_contains($query->sql, 'from "mil_spec_parts"')) {
            throw new RuntimeException('Simulated failure after reference data was read');
        }
    });

    $result = $this->lookup->lookup('niin', '000000045');

    expect($result['status'])->toBe('operational_failure')
        ->and($result['references'])->toBeEmpty()
        ->and($result['items'])->toBeEmpty()
        ->and($result['companies'])->toBeEmpty()
        ->and($result['relationships'])->toBeEmpty()
        ->and($result['has_more'])->toBeFalse();
});
