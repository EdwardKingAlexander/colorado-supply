<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles and permissions
    $this->artisan('permission:cache-reset');

    // Create permissions
    Permission::create(['name' => 'users.viewAny', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.create', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.update', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.delete', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.assignRoles', 'guard_name' => 'web']);
    Permission::create(['name' => 'users.assignPermissions', 'guard_name' => 'web']);

    // Create roles
    $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $superAdmin->givePermissionTo(Permission::all());

    $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $admin->givePermissionTo(Permission::all());

    $salesManager = Role::create(['name' => 'sales_manager', 'guard_name' => 'web']);
    $salesManager->givePermissionTo(['users.viewAny', 'users.view', 'users.update']);

    $salesRep = Role::create(['name' => 'sales_rep', 'guard_name' => 'web']);

    $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
});

test('super_admin can do everything', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    expect($superAdmin->can('users.viewAny'))->toBeTrue()
        ->and($superAdmin->can('users.create'))->toBeTrue()
        ->and($superAdmin->can('users.update'))->toBeTrue()
        ->and($superAdmin->can('users.delete'))->toBeTrue()
        ->and($superAdmin->can('users.assignRoles'))->toBeTrue()
        ->and($superAdmin->can('users.assignPermissions'))->toBeTrue();
});

test('admin can create and update users but has all permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($admin->can('users.viewAny'))->toBeTrue()
        ->and($admin->can('users.create'))->toBeTrue()
        ->and($admin->can('users.update'))->toBeTrue()
        ->and($admin->can('users.delete'))->toBeTrue()
        ->and($admin->can('users.assignRoles'))->toBeTrue();
});

test('sales_manager can update users but cannot delete or assign roles', function () {
    $salesManager = User::factory()->create();
    $salesManager->assignRole('sales_manager');

    expect($salesManager->can('users.viewAny'))->toBeTrue()
        ->and($salesManager->can('users.update'))->toBeTrue()
        ->and($salesManager->can('users.delete'))->toBeFalse()
        ->and($salesManager->can('users.assignRoles'))->toBeFalse()
        ->and($salesManager->can('users.assignPermissions'))->toBeFalse();
});

test('sales_rep cannot access users index', function () {
    $salesRep = User::factory()->create();
    $salesRep->assignRole('sales_rep');

    expect($salesRep->can('users.viewAny'))->toBeFalse()
        ->and($salesRep->can('users.create'))->toBeFalse()
        ->and($salesRep->can('users.update'))->toBeFalse();
});

test('viewer has no user management permissions', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    expect($viewer->can('users.viewAny'))->toBeFalse()
        ->and($viewer->can('users.create'))->toBeFalse()
        ->and($viewer->can('users.update'))->toBeFalse()
        ->and($viewer->can('users.delete'))->toBeFalse();
});

test('user policy prevents deleting yourself', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($user->can('delete', $user))->toBeFalse();
});

test('user policy prevents deleting last super_admin', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $anotherAdmin = User::factory()->create();
    $anotherAdmin->assignRole('admin');

    // Cannot delete the last super_admin
    expect($anotherAdmin->can('delete', $superAdmin))->toBeFalse();
});

test('can delete super_admin when multiple exist', function () {
    $superAdmin1 = User::factory()->create();
    $superAdmin1->assignRole('super_admin');

    $superAdmin2 = User::factory()->create();
    $superAdmin2->assignRole('super_admin');

    // Can delete one when multiple exist
    expect($superAdmin1->can('delete', $superAdmin2))->toBeTrue();
});

test('Gate::before grants all permissions to super_admin', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    // Even without explicit permission, super_admin can do anything
    expect($superAdmin->can('any-random-permission'))->toBeTrue();
});
