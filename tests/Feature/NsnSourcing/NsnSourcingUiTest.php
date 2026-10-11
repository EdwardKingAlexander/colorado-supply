<?php

use App\Filament\Pages\FetchNsnData;
use App\Filament\Resources\NsnSourcingCases\NsnSourcingCaseResource;
use App\Filament\Resources\NsnSourcingCases\Pages\ListNsnSourcingCases;
use App\Filament\Resources\NsnSourcingCases\Pages\ViewNsnSourcingCase;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Models\NsnReference;
use App\Models\NsnSourcingCase;
use App\Models\NsnSourcingFile;
use App\Services\NsnSourcing\NsnSourcingService;
use App\Services\PubLog\PubLogCatalog;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\PubLogFixtures;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/nsn-sourcing-ui-'.bin2hex(random_bytes(8));
    config(['nsn_lookup.directory' => $this->directory.'/owned', 'nsn_lookup.minimum_free_bytes' => 0]);
    $this->travelTo(now()->setDate(2026, 10, 10));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->home = Company::factory()->create();
    $this->other = Company::factory()->create();
    $this->admin = Admin::factory()->create(['has_email_authentication' => true]);
    Storage::fake('local');
});

afterEach(fn () => File::deleteDirectory($this->directory));

function activateUiFixture($test): void
{
    $import = PubLogFixtures::ready($test->directory.'/source');
    app(PubLogCatalog::class)->activate($import->id, null);
}

it('starts a case from a lookup reference and then shows its status on the row', function () {
    activateUiFixture($this);
    $this->actingAs($this->admin, 'admin');
    $reference = NsnReference::where('niin', '000000045')->where('cage_code', '24039')->first();

    $page = Livewire::test(FetchNsnData::class)->fillForm(['type' => 'niin', 'query' => '000000045'])->call('fetchNsnData')
        ->assertSee('Start sourcing case')
        ->call('startSourcingCase', $reference->id);
    $case = NsnSourcingCase::sole();
    $page->assertRedirect(NsnSourcingCaseResource::getUrl('view', ['record' => $case]));
    expect($case->cage_code)->toBe('24039')->and($case->company_id)->toBe($this->home->id);

    Livewire::test(FetchNsnData::class)->fillForm(['type' => 'niin', 'query' => '000000045'])->call('fetchNsnData')
        ->assertSee('Open sourcing case')->assertSee('No confirmation yet');

    app(NsnSourcingService::class)->recordResponse($case, ['received_on' => '2026-10-09', 'channel' => 'email', 'body' => 'Still made.', 'production_state' => 'current', 'sells_direct' => 'yes']);
    Livewire::test(FetchNsnData::class)->fillForm(['type' => 'niin', 'query' => '000000045'])->call('fetchNsnData')
        ->assertSee('Current')->assertSee('recorded response 2026-10-09');
});

it('ignores start requests for references outside the current result', function () {
    activateUiFixture($this);
    $this->actingAs($this->admin, 'admin');

    Livewire::test(FetchNsnData::class)->call('startSourcingCase', NsnReference::first()->id)->assertNoRedirect();
    expect(NsnSourcingCase::withoutGlobalScopes()->count())->toBe(0);
});

it('lists only the home company cases and blocks viewing another company case', function () {
    $mine = NsnSourcingCase::factory()->for($this->home)->create();
    $theirs = NsnSourcingCase::factory()->for($this->other)->create();
    $closed = NsnSourcingCase::factory()->for($this->home)->create(['status' => 'closed', 'open_key' => null]);
    $this->actingAs($this->admin, 'admin');

    $this->get(NsnSourcingCaseResource::getUrl('index'))->assertOk()->assertSee('Sourcing Cases');
    Livewire::test(ListNsnSourcingCases::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$theirs, $closed]);
    $this->get(NsnSourcingCaseResource::getUrl('view', ['record' => $mine]))->assertOk()->assertSee('PUB LOG snapshot - not verified');
    $this->get(NsnSourcingCaseResource::getUrl('view', ['record' => $theirs]))->assertNotFound();
});

it('runs the whole case from verification to supplier link without sending anything', function () {
    Mail::fake();
    Notification::fake();
    $case = NsnSourcingCase::factory()->for($this->home)->create(['nsn' => '5940000000045', 'niin' => '000000045', 'part_number' => 'UK 60A890D255', 'cage_code' => '24039']);
    $this->actingAs($this->admin, 'admin');
    $page = fn () => Livewire::test(ViewNsnSourcingCase::class, ['record' => $case->getRouteKey()]);

    $page()->callAction('verifyCage', data: ['outcome' => 'verified', 'verified_name' => 'VARO LLC', 'source_url' => 'https://cage.dla.mil/Search/Details?id=24039', 'verified_on' => '2026-10-10'])
        ->assertHasNoActionErrors();
    $page()->callAction('addContact', data: ['company_name' => 'Varo', 'email' => 'sales@varo.example', 'website' => 'https://varo.example', 'source_url' => 'https://varo.example/contact', 'source_on' => '2026-10-10'])
        ->assertHasNoActionErrors();
    $page()->assertActionHidden('markContacted')
        ->callAction('draftInquiry')->assertHasNoActionErrors();

    $inquiry = $case->inquiries()->sole();
    expect($inquiry->body)->toContain('5940-00-000-0045')->toContain('UK 60A890D255')->and($inquiry->isContacted())->toBeFalse()
        ->and(NsnSourcingCaseResource::mailto($inquiry))->toStartWith('mailto:sales%40varo.example?subject=');
    $page()->assertSee('Draft - not sent')->assertSee('Open in email app')
        ->callAction('markContacted', data: ['inquiry_id' => $inquiry->id, 'contacted_on' => '2026-10-10', 'follow_up_on' => '2026-10-17'])->assertHasNoActionErrors();
    expect($case->fresh()->status)->toBe('awaiting_response');

    $page()->callAction('recordResponse', data: [
        'received_on' => '2026-10-10', 'channel' => 'email', 'body' => 'We still produce this part. MOQ 10.',
        'production_state' => 'current', 'sells_direct' => 'yes', 'lead_time_days' => 30, 'minimum_order' => '10',
        'distributors' => [['name' => 'Acme Supply', 'cage_code' => '1AB23']],
        'files' => [UploadedFile::fake()->create('quote.pdf', 50, 'application/pdf')],
    ])->assertHasNoActionErrors();
    $case->refresh();
    expect($case->production_state)->toBe('current')->and($case->status)->toBe('answered');

    $file = NsnSourcingFile::sole();
    $page()->assertSee('quote.pdf')->assertSee('Acme Supply (CAGE 1AB23)')
        ->call('downloadEvidence', $file->id)->assertFileDownloaded('quote.pdf');

    MilSpecPart::create(['nsn' => '5940-00-000-0045', 'description' => 'TERMINAL BOARD', 'manufacturer_id' => Manufacturer::create(['name' => 'Varo LLC'])->id]);
    $page()->callAction('linkSupplier', data: ['name' => 'VARO LLC', 'cage_code' => '24039'])->assertHasNoActionErrors();
    expect(MilSpecPart::first()->suppliers()->first()->cage_code)->toBe('24039');

    $page()->callAction('close', data: ['close_reason' => 'Supplier confirmed'])->assertHasNoActionErrors();
    $page()->assertActionHidden('recordResponse')->assertActionHidden('draftInquiry');

    Mail::assertNothingSent();
    Mail::assertNothingQueued();
    Notification::assertNothingSent();
});

it('refuses evidence downloads that belong to another case or company', function () {
    $mine = NsnSourcingCase::factory()->for($this->home)->create();
    $otherMine = NsnSourcingCase::factory()->for($this->home)->create();
    $this->actingAs($this->admin, 'admin');
    $response = app(NsnSourcingService::class)->recordResponse($otherMine, ['received_on' => '2026-10-09', 'channel' => 'email', 'body' => 'x', 'production_state' => 'unknown', 'sells_direct' => 'unknown'],
        [UploadedFile::fake()->create('secret.pdf', 5, 'application/pdf')]);
    $theirs = NsnSourcingCase::factory()->for($this->other)->create();
    $theirFile = NsnSourcingFile::withoutGlobalScopes()->create(['company_id' => $this->other->id, 'nsn_sourcing_response_id' => $theirs->responses()->withoutGlobalScopes()->create(['company_id' => $this->other->id, 'received_on' => '2026-10-09', 'channel' => 'email', 'body' => 'x', 'production_state' => 'unknown', 'actor_type' => Admin::class, 'actor_id' => $this->admin->id])->id,
        'path' => 'nsn-sourcing/x.pdf', 'original_name' => 'theirs.pdf', 'mime_type' => 'application/pdf', 'size' => 1, 'sha256' => str_repeat('a', 64), 'actor_type' => Admin::class, 'actor_id' => $this->admin->id]);

    Livewire::test(ViewNsnSourcingCase::class, ['record' => $mine->getRouteKey()])->call('downloadEvidence', $response->files()->first()->id)->assertNotFound();
    Livewire::test(ViewNsnSourcingCase::class, ['record' => $mine->getRouteKey()])->call('downloadEvidence', $theirFile->id)->assertNotFound();
});

it('reports invalid evidence in a notification instead of saving', function () {
    $case = NsnSourcingCase::factory()->for($this->home)->create();
    $this->actingAs($this->admin, 'admin');

    Livewire::test(ViewNsnSourcingCase::class, ['record' => $case->getRouteKey()])->callAction('recordResponse', data: [
        'received_on' => '2026-10-10', 'channel' => 'email', 'body' => 'See attached', 'production_state' => 'current', 'sells_direct' => 'unknown',
        'files' => [UploadedFile::fake()->create('payload.exe', 5, 'application/x-msdownload')],
    ])->assertNotified('Not saved');

    expect($case->responses()->count())->toBe(0)->and($case->fresh()->production_state)->toBe('unknown');
});
