<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles and permissions
    $this->artisan('permission:cache-reset');
    $this->artisan('migrate');

    // Create CRM permissions
    $crmPermissions = [
        'crm.customers.viewAny',
        'crm.customers.view',
        'crm.customers.create',
        'crm.customers.update',
        'crm.customers.delete',
        'crm.opportunities.viewAny',
        'crm.opportunities.view',
        'crm.opportunities.create',
        'crm.opportunities.update',
        'crm.opportunities.delete',
        'crm.pipelines.manage',
        'crm.stages.manage',
        'crm.reports.view',
        'crm.activities.manage',
        'crm.attachments.manage',
    ];

    foreach ($crmPermissions as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }

    // Create roles
    $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin->givePermissionTo(Permission::all());

    $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $admin->givePermissionTo(Permission::all());

    $salesManager = Role::create(['name' => 'sales_manager', 'guard_name' => 'web']);
    $salesManager->givePermissionTo([
        'crm.customers.viewAny',
        'crm.customers.view',
        'crm.customers.create',
        'crm.customers.update',
        'crm.customers.delete',
        'crm.opportunities.viewAny',
        'crm.opportunities.view',
        'crm.opportunities.create',
        'crm.opportunities.update',
        'crm.opportunities.delete',
        'crm.pipelines.manage',
        'crm.stages.manage',
        'crm.reports.view',
        'crm.activities.manage',
        'crm.attachments.manage',
    ]);

    $salesRep = Role::create(['name' => 'sales_rep', 'guard_name' => 'web']);
    $salesRep->givePermissionTo([
        'crm.customers.viewAny',
        'crm.customers.view',
        'crm.customers.create',
        'crm.customers.update',
        'crm.opportunities.viewAny',
        'crm.opportunities.view',
        'crm.opportunities.create',
        'crm.opportunities.update',
        'crm.opportunities.delete',
        'crm.activities.manage',
        'crm.attachments.manage',
    ]);

    $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
    $viewer->givePermissionTo([
        'crm.customers.viewAny',
        'crm.customers.view',
        'crm.opportunities.viewAny',
        'crm.opportunities.view',
        'crm.reports.view',
    ]);

    // Create test pipeline and stage
    $pipeline = Pipeline::create(['name' => 'Test Pipeline', 'is_default' => true]);
    $stage = Stage::create([
        'pipeline_id' => $pipeline->id,
        'name' => 'Qualification',
        'position' => 0,
        'probability_default' => 10,
    ]);
});

test('super_admin has full CRM access', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    expect($superAdmin->can('crm.customers.viewAny'))->toBeTrue()
        ->and($superAdmin->can('crm.customers.create'))->toBeTrue()
        ->and($superAdmin->can('crm.customers.delete'))->toBeTrue()
        ->and($superAdmin->can('crm.opportunities.viewAny'))->toBeTrue()
        ->and($superAdmin->can('crm.pipelines.manage'))->toBeTrue();
});

test('sales_manager can manage all CRM', function () {
    $salesManager = User::factory()->create();
    $salesManager->assignRole('sales_manager');

    expect($salesManager->can('crm.customers.viewAny'))->toBeTrue()
        ->and($salesManager->can('crm.customers.create'))->toBeTrue()
        ->and($salesManager->can('crm.opportunities.update'))->toBeTrue()
        ->and($salesManager->can('crm.pipelines.manage'))->toBeTrue()
        ->and($salesManager->can('crm.reports.view'))->toBeTrue();
});

test('sales_rep can create and update own opportunities only', function () {
    $salesRep = User::factory()->create();
    $salesRep->assignRole('sales_rep');

    $customer = Customer::create([
        'name' => 'Test Customer',
        'owner_id' => $salesRep->id,
    ]);

    $stage = Stage::first();
    $pipeline = Pipeline::first();

    $ownOpportunity = Opportunity::create([
        'customer_id' => $customer->id,
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stage->id,
        'title' => 'Own Deal',
        'amount' => 10000,
        'owner_id' => $salesRep->id,
        'created_by' => $salesRep->id,
    ]);

    $otherUser = User::factory()->create();
    $otherOpportunity = Opportunity::create([
        'customer_id' => $customer->id,
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stage->id,
        'title' => 'Other Deal',
        'amount' => 5000,
        'owner_id' => $otherUser->id,
        'created_by' => $otherUser->id,
    ]);

    // Can update own
    expect($salesRep->can('update', $ownOpportunity))->toBeTrue()
        // Can view others
        ->and($salesRep->can('view', $otherOpportunity))->toBeTrue()
        // Cannot update others
        ->and($salesRep->can('update', $otherOpportunity))->toBeFalse();
});

test('sales_rep cannot manage pipelines or stages', function () {
    $salesRep = User::factory()->create();
    $salesRep->assignRole('sales_rep');

    expect($salesRep->can('crm.pipelines.manage'))->toBeFalse()
        ->and($salesRep->can('crm.stages.manage'))->toBeFalse();
});

test('viewer has read-only access', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    expect($viewer->can('crm.customers.viewAny'))->toBeTrue()
        ->and($viewer->can('crm.customers.view'))->toBeTrue()
        ->and($viewer->can('crm.customers.create'))->toBeFalse()
        ->and($viewer->can('crm.customers.update'))->toBeFalse()
        ->and($viewer->can('crm.customers.delete'))->toBeFalse()
        ->and($viewer->can('crm.opportunities.viewAny'))->toBeTrue()
        ->and($viewer->can('crm.opportunities.view'))->toBeTrue()
        ->and($viewer->can('crm.opportunities.create'))->toBeFalse()
        ->and($viewer->can('crm.reports.view'))->toBeTrue();
});

test('admin can update any customer', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $otherUser = User::factory()->create();
    $customer = Customer::create([
        'name' => 'Test Customer',
        'owner_id' => $otherUser->id,
    ]);

    expect($admin->can('update', $customer))->toBeTrue();
});

test('sales_rep can only update own customers', function () {
    $salesRep = User::factory()->create();
    $salesRep->assignRole('sales_rep');

    $ownCustomer = Customer::create([
        'name' => 'Own Customer',
        'owner_id' => $salesRep->id,
    ]);

    $otherUser = User::factory()->create();
    $otherCustomer = Customer::create([
        'name' => 'Other Customer',
        'owner_id' => $otherUser->id,
    ]);

    expect($salesRep->can('update', $ownCustomer))->toBeTrue()
        ->and($salesRep->can('update', $otherCustomer))->toBeFalse();
});
