<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Models\NsnReference;
use App\Models\NsnSalesContact;
use App\Models\NsnSourcingCase;
use App\Models\NsnSourcingResponse;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorContact;
use App\Services\NsnSourcing\NsnSourcingService;
use App\Services\PubLog\PubLogCatalog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\PubLogFixtures;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/nsn-sourcing-'.bin2hex(random_bytes(8));
    config(['nsn_lookup.directory' => $this->directory.'/owned', 'nsn_lookup.minimum_free_bytes' => 0]);
    $this->travelTo(now()->setDate(2026, 10, 10));
    $this->home = Company::factory()->create();
    $this->other = Company::factory()->create();
    $this->admin = Admin::factory()->create();
    $this->service = app(NsnSourcingService::class);
    Storage::fake('local');
});

afterEach(fn () => File::deleteDirectory($this->directory));

function activatedReference($test): NsnReference
{
    $import = PubLogFixtures::ready($test->directory.'/source');
    app(PubLogCatalog::class)->activate($import->id, null);

    return NsnReference::where('niin', '000000045')->orderBy('id')->first();
}

function answer(array $overrides = []): array
{
    return [
        'received_on' => '2026-10-09', 'channel' => 'email', 'body' => 'Yes, we still make it.',
        'production_state' => 'current', 'sells_direct' => 'yes', ...$overrides,
    ];
}

it('opens one case per company and reference from an activated release and reuses it while open', function () {
    $reference = activatedReference($this);
    $this->actingAs($this->admin, 'admin');

    $case = $this->service->openFromReference($reference->id);

    expect($case->company_id)->toBe($this->home->id)
        ->and($case->nsn)->toBe('5940000000045')->and($case->cage_code)->toBe($reference->cage_code)
        ->and($case->part_number)->toBe($reference->part_number)->and($case->item_name)->toBe('TERMINAL BOARD ASSEMBLY')
        ->and($case->release_date->format('Y-m-d'))->toBe('2026-10-01')
        ->and($case->opened_by_type)->toBe(Admin::class)->and($case->opened_by_id)->toBe($this->admin->id)
        ->and($case->production_state)->toBe('unknown')
        ->and($this->service->openFromReference($reference->id)->id)->toBe($case->id);

    $this->service->close($case, 'Duplicate');
    expect($this->service->openFromReference($reference->id)->id)->not->toBe($case->id)
        ->and(NsnSourcingCase::count())->toBe(2);
});

it('refuses references from a release that was never activated', function () {
    PubLogFixtures::ready($this->directory.'/source');
    $this->actingAs($this->admin, 'admin');

    expect(fn () => $this->service->openFromReference(NsnReference::first()->id))->toThrow(HttpException::class);
    expect(NsnSourcingCase::withoutGlobalScopes()->count())->toBe(0);
});

it('isolates cases by company for admins, users and users without a company', function () {
    $mine = NsnSourcingCase::factory()->for($this->home)->create();
    $theirs = NsnSourcingCase::factory()->for($this->other)->create();

    $this->actingAs($this->admin, 'admin');
    expect(NsnSourcingCase::pluck('id')->all())->toBe([$mine->id])
        ->and($this->admin->can('view', $mine))->toBeTrue()
        ->and($this->admin->can('view', $theirs))->toBeFalse()
        ->and(fn () => $this->service->recordVerification($theirs, ['outcome' => 'unresolved', 'source_url' => 'https://cage.dla.mil', 'verified_on' => '2026-10-10']))
        ->toThrow(AuthorizationException::class);

    $user = User::factory()->create(['company_id' => $this->other->id]);
    $this->actingAs($user, 'web');
    expect(NsnSourcingCase::pluck('id')->all())->toBe([$theirs->id])
        ->and($user->can('view', $theirs))->toBeTrue()->and($user->can('view', $mine))->toBeFalse();

    $loner = User::factory()->create(['company_id' => null]);
    $this->actingAs($loner, 'web');
    expect(NsnSourcingCase::count())->toBe(0)
        ->and($loner->can('viewAny', NsnSourcingCase::class))->toBeFalse()
        ->and($loner->can('view', $theirs))->toBeFalse();
});

it('ignores a posted company and keeps evidence append-only and logged', function () {
    $case = NsnSourcingCase::factory()->for($this->home)->create();
    $this->actingAs($this->admin, 'admin');

    $verification = $this->service->recordVerification($case, [
        'company_id' => $this->other->id, 'outcome' => 'verified', 'verified_name' => 'VARO LLC',
        'source_url' => 'https://cage.dla.mil/Search/Details?id=1', 'verified_on' => '2026-10-10',
    ]);

    expect($verification->company_id)->toBe($this->home->id)
        ->and($verification->cage_code)->toBe($case->cage_code)
        ->and($verification->actor_type)->toBe(Admin::class)
        ->and(fn () => $verification->update(['outcome' => 'unresolved']))->toThrow(LogicException::class)
        ->and(fn () => $verification->delete())->toThrow(LogicException::class)
        ->and(Activity::where('log_name', 'nsn-sourcing')->where('company_id', $this->home->id)->count())->toBeGreaterThan(0);

    expect(fn () => $this->service->recordVerification($case, ['outcome' => 'verified', 'source_url' => 'not a url', 'verified_on' => '2026-10-10']))
        ->toThrow(ValidationException::class);
});

it('requires a source for contacts and never copies them into shared records', function () {
    $case = NsnSourcingCase::factory()->for($this->home)->create();
    $vendorContacts = VendorContact::count();
    $this->actingAs($this->admin, 'admin');

    expect(fn () => $this->service->addContact($case, ['email' => 'sales@varo.example', 'source_on' => '2026-10-10']))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->addContact($case, ['contact_name' => 'Pat', 'source_url' => 'https://varo.example/contact', 'source_on' => '2026-10-10']))->toThrow(ValidationException::class);

    $contact = $this->service->addContact($case, ['company_name' => 'Varo', 'email' => 'sales@varo.example', 'source_url' => 'https://varo.example/contact', 'source_on' => '2026-10-10']);
    $this->service->addContact($case, ['phone' => '555-0100', 'source_url' => 'https://varo.example/about', 'source_on' => '2026-10-01']);

    expect($case->contacts()->count())->toBe(2)->and($contact->company_id)->toBe($this->home->id)
        ->and(VendorContact::count())->toBe($vendorContacts)->and(Supplier::count())->toBe(0);
});

it('keeps drafts separate from contact and sends nothing', function () {
    Mail::fake();
    Notification::fake();
    $case = NsnSourcingCase::factory()->for($this->home)->create();
    $this->actingAs($this->admin, 'admin');
    $contact = $this->service->addContact($case, ['email' => 'sales@varo.example', 'source_url' => 'https://varo.example', 'source_on' => '2026-10-10']);

    $inquiry = $this->service->draftInquiry($case, ['nsn_sales_contact_id' => $contact->id, 'channel' => 'email', 'body' => 'Do you still make it?']);
    expect($inquiry->isContacted())->toBeFalse()->and($case->fresh()->status)->toBe('open');

    $this->service->markContacted($inquiry, ['contacted_on' => '2026-10-10', 'follow_up_on' => '2026-10-17']);
    $case->refresh();
    expect($inquiry->fresh()->isContacted())->toBeTrue()->and($case->status)->toBe('awaiting_response')
        ->and($case->follow_up_on->format('Y-m-d'))->toBe('2026-10-17')
        ->and(fn () => $this->service->markContacted($inquiry->fresh(), ['contacted_on' => '2026-10-10']))->toThrow(HttpException::class);

    $otherCase = NsnSourcingCase::factory()->for($this->home)->create();
    expect(fn () => $this->service->draftInquiry($otherCase, ['nsn_sales_contact_id' => $contact->id, 'channel' => 'email', 'body' => 'x']))->toThrow(ValidationException::class);

    Mail::assertNothingSent();
    Mail::assertNothingQueued();
    Notification::assertNothingSent();
});

it('derives production state only from recorded responses, keeping conflicts visible', function () {
    $case = NsnSourcingCase::factory()->for($this->home)->create();
    $this->actingAs($this->admin, 'admin');
    expect($case->production_state)->toBe('unknown');

    $this->service->recordResponse($case, answer(['production_state' => 'unknown', 'body' => 'We will check.']));
    expect($case->fresh()->production_state)->toBe('unknown')->and($case->fresh()->status)->toBe('answered');

    $current = $this->service->recordResponse($case, answer());
    expect($case->fresh()->production_state)->toBe('current');

    $discontinued = $this->service->recordResponse($case, answer(['production_state' => 'discontinued', 'body' => 'Discontinued in 2024.', 'channel' => 'phone']));
    expect($case->fresh()->production_state)->toBe('conflicting')->and($case->responses()->count())->toBe(3);

    $this->service->recordResponse($case, answer(['production_state' => 'discontinued', 'body' => 'Confirmed discontinued; earlier email was wrong.', 'supersedes_response_id' => $current->id]));
    expect($case->fresh()->production_state)->toBe('discontinued')
        ->and(fn () => $discontinued->update(['body' => 'edited']))->toThrow(LogicException::class);

    $otherCase = NsnSourcingCase::factory()->for($this->home)->create();
    expect(fn () => $this->service->recordResponse($otherCase, answer(['supersedes_response_id' => $current->id])))->toThrow(ValidationException::class);

    $this->service->close($case, 'Sourced elsewhere');
    expect(fn () => $this->service->recordResponse($case->fresh(), answer()))->toThrow(HttpException::class);
});

it('stores allowed evidence files privately with a hash and rejects other types', function () {
    $case = NsnSourcingCase::factory()->for($this->home)->create();
    $this->actingAs($this->admin, 'admin');

    $response = $this->service->recordResponse($case, answer([
        'distributors' => [['name' => 'Acme Supply', 'cage_code' => '1ab23', 'authorization' => 'Letter on file']],
        'replacement_nsn' => '5940-01-234-5678',
    ]), [UploadedFile::fake()->create('quote.pdf', 120, 'application/pdf'), UploadedFile::fake()->image('reply.png')]);

    $file = $response->files()->first();
    expect($response->files()->count())->toBe(2)->and($response->distributors[0]['cage_code'])->toBe('1AB23')
        ->and($response->replacement_nsn)->toBe('5940012345678')
        ->and($file->path)->toStartWith("nsn-sourcing/{$this->home->id}/{$case->id}/")
        ->and($file->original_name)->toBe('quote.pdf')->and(strlen($file->sha256))->toBe(64);
    Storage::disk('local')->assertExists($file->path);

    expect(fn () => $this->service->recordResponse($case, answer(), [UploadedFile::fake()->create('evil.exe', 10, 'application/x-msdownload')]))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->recordResponse($case, answer(), [UploadedFile::fake()->create('fake.pdf', 10, 'text/html')]))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->recordResponse($case, answer(), [UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf')]))->toThrow(ValidationException::class)
        ->and(NsnSourcingResponse::count())->toBe(1);
});

it('links an answered case to the curated part with shared supplier facts only', function () {
    $case = NsnSourcingCase::factory()->for($this->home)->create(['nsn' => '5940000000045', 'niin' => '000000045', 'part_number' => 'UK 60A890D255']);
    $this->actingAs($this->admin, 'admin');
    $this->service->addContact($case, ['email' => 'private@varo.example', 'source_url' => 'https://varo.example', 'source_on' => '2026-10-10']);

    expect(fn () => $this->service->linkSupplier($case, ['name' => 'Varo LLC']))->toThrow(HttpException::class);
    $this->service->recordResponse($case, answer());
    expect(fn () => $this->service->linkSupplier($case->fresh(), ['name' => 'Varo LLC']))->toThrow(ValidationException::class);

    $part = MilSpecPart::create(['nsn' => '5940-00-000-0045', 'description' => 'TERMINAL BOARD', 'manufacturer_id' => Manufacturer::create(['name' => 'Varo LLC', 'cage_code' => '24039'])->id]);
    $supplier = $this->service->linkSupplier($case->fresh(), ['name' => 'Varo LLC', 'cage_code' => '24039', 'website' => 'https://varo.example']);

    expect($part->suppliers()->first()->id)->toBe($supplier->id)
        ->and($part->suppliers()->first()->pivot->supplier_part_number)->toBe('UK 60A890D255')
        ->and($supplier->contact_info)->toBeNull()
        ->and($case->fresh()->mil_spec_part_id)->toBe($part->id)
        ->and(NsnSalesContact::first()->supplier_id)->toBeNull();
});
