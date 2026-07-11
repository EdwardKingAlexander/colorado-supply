<?php

use App\Models\Company;
use App\Models\CompanyEmailDomain;
use App\Models\Location;
use App\Models\User;
use App\Services\Organizations\CompanyDomainService;
use Illuminate\Support\Facades\Gate;

function enforcedLocationCompany(string $domain = 'example.com'): array
{
    $company = Company::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => $domain]);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'member@'.$domain,
    ]);
    app(CompanyDomainService::class)->activate($company);

    return [$company->fresh(), $user->fresh()];
}

it('grants location management to matching members of an enforced company', function () {
    [$company, $user] = enforcedLocationCompany();
    $location = Location::create([
        'company_id' => $company->id,
        'name' => 'Denver',
        'slug' => 'denver',
    ]);

    $this->actingAs($user);

    expect(Gate::allows('viewAny', Location::class))->toBeTrue()
        ->and(Gate::allows('create', Location::class))->toBeTrue()
        ->and(Gate::allows('update', $location))->toBeTrue()
        ->and(Gate::allows('delete', $location))->toBeTrue();
});

it('denies location management until company domain enforcement is ready', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user);

    expect(Gate::allows('viewAny', Location::class))->toBeFalse()
        ->and(Gate::allows('create', Location::class))->toBeFalse();
});

it('denies cross-company location access even when both users have valid domains', function () {
    [$companyA, $userA] = enforcedLocationCompany('alpha.test');
    [$companyB] = enforcedLocationCompany('bravo.test');
    $foreignLocation = Location::create([
        'company_id' => $companyB->id,
        'name' => 'Foreign',
        'slug' => 'foreign',
    ]);

    $this->actingAs($userA);

    expect(Gate::allows('view', $foreignLocation))->toBeFalse()
        ->and(Gate::allows('update', $foreignLocation))->toBeFalse()
        ->and($foreignLocation->company_id)->not->toBe($companyA->id);
});
