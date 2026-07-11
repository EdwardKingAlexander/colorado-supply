<?php

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyEmailDomain;
use App\Models\Location;
use App\Models\User;
use App\Services\Organizations\CompanyDomainService;

function auditedLocationUser(string $domain = 'example.com'): array
{
    $company = Company::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => $domain]);
    $user = User::factory()->create(['company_id' => $company->id, 'email' => 'member@'.$domain]);
    app(CompanyDomainService::class)->activate($company);

    return [$company->fresh(), $user->fresh()];
}

it('records company-indexed location lifecycle changes with the web actor', function () {
    [$company, $user] = auditedLocationUser();

    $locationId = $this->actingAs($user)
        ->postJson(route('dashboard.locations.store'), ['name' => 'Denver'])
        ->assertCreated()
        ->json('data.id');

    $location = Location::findOrFail($locationId);

    $this->patchJson(route('dashboard.locations.update', $location), [
        'name' => 'Aurora',
        'parent_id' => null,
    ])->assertOk();

    $this->deleteJson(route('dashboard.locations.destroy', $location))->assertOk();
    $this->postJson(route('dashboard.locations.restore', $location->id))->assertOk();

    $activities = ActivityLog::query()
        ->where('company_id', $company->id)
        ->where('subject_type', Location::class)
        ->where('subject_id', $location->id)
        ->orderBy('id')
        ->get();

    expect($activities->pluck('event')->all())
        ->toBe(['created', 'updated', 'deleted', 'restored'])
        ->and($activities->every(fn (ActivityLog $activity) => $activity->causer?->is($user)))->toBeTrue()
        ->and($activities->every(fn (ActivityLog $activity) => $activity->company_id === $company->id))->toBeTrue()
        ->and($activities->get(1)->properties->get('old')['name'])->toBe('Denver')
        ->and($activities->get(1)->properties->get('attributes')['name'])->toBe('Aurora');
});

it('returns only same-company organization activity to normal members', function () {
    [$companyA, $userA] = auditedLocationUser('alpha.test');
    [$companyB, $userB] = auditedLocationUser('bravo.test');

    $this->actingAs($userA)->postJson(route('dashboard.locations.store'), ['name' => 'Alpha'])->assertCreated();
    $this->actingAs($userB)->postJson(route('dashboard.locations.store'), ['name' => 'Bravo'])->assertCreated();

    $response = $this->actingAs($userA)
        ->getJson(route('dashboard.organization-history.index'))
        ->assertOk();

    $subjectIds = collect($response->json('data'))->pluck('subject_id');

    expect($subjectIds)->toContain(Location::where('company_id', $companyA->id)->value('id'))
        ->not->toContain(Location::where('company_id', $companyB->id)->value('id'));
});

it('marks administrator-caused location changes as overrides', function () {
    $company = Company::factory()->create();
    $location = Location::create(['company_id' => $company->id, 'name' => 'Before', 'slug' => 'before']);
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin');
    $location->update(['name' => 'After']);

    $activity = ActivityLog::query()
        ->where('company_id', $company->id)
        ->where('subject_type', Location::class)
        ->where('subject_id', $location->id)
        ->where('event', 'updated')
        ->latest('id')
        ->firstOrFail();

    expect($activity->causer?->is($admin))->toBeTrue()
        ->and($activity->properties->get('administrator_override'))->toBeTrue();
});
