<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyEmailDomain;
use App\Models\User;
use App\Services\Organizations\CompanyDomainService;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

it('supports multiple normalized domains while keeping them unique across companies', function () {
    $company = Company::factory()->create();

    $first = CompanyEmailDomain::factory()->for($company)->create(['domain' => 'Example.COM']);
    $second = CompanyEmailDomain::factory()->for($company)->create(['domain' => '@subsidiary.example']);

    expect($first->domain)->toBe('example.com')
        ->and($first->is_primary)->toBeTrue()
        ->and($second->domain)->toBe('subsidiary.example')
        ->and($second->is_primary)->toBeFalse();

    $otherCompany = Company::factory()->create();

    expect(fn () => CompanyEmailDomain::factory()->for($otherCompany)->create(['domain' => 'example.com']))
        ->toThrow(QueryException::class);
});

it('activates enforcement only after all existing members match an approved domain', function () {
    $company = Company::factory()->create();
    $admin = Admin::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => 'example.com']);
    $matching = User::factory()->create(['company_id' => $company->id, 'email' => 'person@example.com']);
    $mismatched = User::factory()->create(['company_id' => $company->id, 'email' => 'person@other.com']);
    $service = app(CompanyDomainService::class);

    expect(fn () => $service->activate($company, $admin))
        ->toThrow(ValidationException::class);

    expect($company->fresh()->domain_enforcement_enabled_at)->toBeNull();

    $mismatched->update(['email' => 'second@example.com']);
    $activated = $service->activate($company, $admin);

    expect($activated->domain_enforcement_enabled_at)->not->toBeNull()
        ->and($service->mismatchedUsers($activated))->toBeEmpty()
        ->and($matching->fresh()->company_id)->toBe($company->id);

    $activity = Activity::query()
        ->where('event', 'domain_enforcement_activated')
        ->latest('id')
        ->firstOrFail();

    expect($activity->causer->is($admin))->toBeTrue()
        ->and($activity->properties->get('company_id'))->toBe($company->id);
});

it('blocks mismatched company email changes after enforcement is active', function () {
    $company = Company::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => 'example.com']);
    $user = User::factory()->create(['company_id' => $company->id, 'email' => 'person@example.com']);
    app(CompanyDomainService::class)->activate($company);

    expect(fn () => $user->update(['email' => 'person@example2.com']))
        ->toThrow(ValidationException::class);

    expect($user->fresh()->email)->toBe('person@example.com');
});

it('allows unaffiliated users and pre-activation companies without auto-joining', function () {
    $company = Company::factory()->create();
    $unaffiliated = User::factory()->create(['company_id' => null, 'email' => 'person@outside.com']);
    $member = User::factory()->create(['company_id' => $company->id, 'email' => 'person@old-domain.com']);

    $unaffiliated->update(['email' => 'person@example2.com']);
    $member->update(['email' => 'person@new-domain.com']);

    expect($unaffiliated->fresh()->company_id)->toBeNull()
        ->and($member->fresh()->company_id)->toBe($company->id);
});

it('prevents deleting the final domain while enforcement is active', function () {
    $company = Company::factory()->create();
    $domain = CompanyEmailDomain::factory()->for($company)->create(['domain' => 'example.com']);
    app(CompanyDomainService::class)->activate($company);

    expect(fn () => $domain->delete())->toThrow(ValidationException::class);
    expect($domain->fresh())->not->toBeNull();
});

it('enforces approved domains through the profile endpoint', function () {
    $company = Company::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => 'example.com']);
    $user = User::factory()->create(['company_id' => $company->id, 'email' => 'person@example.com']);
    app(CompanyDomainService::class)->activate($company);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'person@example2.com',
        ])
        ->assertSessionHasErrors('email');

    expect($user->fresh()->email)->toBe('person@example.com');
});

it('reports missing domains and mismatched users before rollout', function () {
    $missing = Company::factory()->create(['name' => 'Missing Domains']);
    User::factory()->create(['company_id' => $missing->id, 'email' => 'person@missing.test']);

    $configured = Company::factory()->create(['name' => 'Configured']);
    CompanyEmailDomain::factory()->for($configured)->create(['domain' => 'configured.test']);
    User::factory()->create(['company_id' => $configured->id, 'email' => 'person@other.test']);

    $this->artisan('organizations:audit-email-domains')
        ->expectsOutputToContain('Missing Domains')
        ->expectsOutputToContain('person@other.test')
        ->assertFailed();
});
