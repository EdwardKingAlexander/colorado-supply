<?php

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\Resources\CompanyResource\RelationManagers\EmailDomainsRelationManager;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyEmailDomain;
use App\Models\User;
use App\Services\Organizations\CompanyDomainService;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs($this->admin = Admin::factory()->create(), 'admin');
    $this->actingAs($this->webAdmin = User::factory()->create());

    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->webAdmin->assignRole($role);
});

it('registers approved email domains on the company resource', function () {
    expect(CompanyResource::getRelations())
        ->toContain(EmailDomainsRelationManager::class);
});

it('creates and normalizes an approved domain through the company relation manager', function () {
    $company = Company::factory()->create();

    Livewire::test(EmailDomainsRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => EditCompany::class,
    ])
        ->callTableAction('create', data: [
            'domain' => '@Example.COM',
            'is_primary' => false,
        ])
        ->assertHasNoTableActionErrors();

    $domain = $company->emailDomains()->sole();

    expect($domain->domain)->toBe('example.com')
        ->and($domain->is_primary)->toBeTrue()
        ->and($domain->approved_by_admin_id)->toBe($this->admin->id)
        ->and($domain->approved_at)->not->toBeNull();
});

it('rejects a mismatched company assignment in the admin user form', function () {
    $company = Company::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => 'example.com']);
    app(CompanyDomainService::class)->activate($company, $this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Mismatched Member',
            'email' => 'member@example2.com',
            'company_id' => $company->id,
            'password' => 'ValidPassword123!',
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);

    expect(User::query()->where('email', 'member@example2.com')->exists())->toBeFalse();
});

it('creates a matching company member in the admin user form', function () {
    $company = Company::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => 'example.com']);
    app(CompanyDomainService::class)->activate($company, $this->admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Matching Member',
            'email' => 'member@example.com',
            'company_id' => $company->id,
            'password' => 'ValidPassword123!',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'member@example.com')->value('company_id'))
        ->toBe($company->id);
});
