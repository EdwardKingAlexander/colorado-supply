<?php

use App\Models\Quote;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles if they don't exist
    $roles = ['admins', 'sales_managers', 'sales_reps', 'super_admins', 'unauthorized_role'];
    foreach ($roles as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }
});

test('unauthorized users cannot view quotes', function () {
    $user = User::factory()->create();
    $user->assignRole('unauthorized_role');
    $quote = Quote::factory()->create(['sales_rep_id' => $user->id]);

    expect($user->can('viewAny', Quote::class))->toBeFalse();
    expect($user->can('view', $quote))->toBeFalse();
    expect($user->can('create', Quote::class))->toBeFalse();
    expect($user->can('update', $quote))->toBeFalse();
    expect($user->can('delete', $quote))->toBeFalse();
});

test('admins can access quotes', function () {
    $user = User::factory()->create();
    $user->assignRole('admins');
    $quote = Quote::factory()->create(['sales_rep_id' => $user->id]);

    expect($user->can('viewAny', Quote::class))->toBeTrue();
    expect($user->can('view', $quote))->toBeTrue();
    expect($user->can('create', Quote::class))->toBeTrue();
    expect($user->can('update', $quote))->toBeTrue();
    expect($user->can('delete', $quote))->toBeTrue();
});

test('sales_managers can access quotes', function () {
    $user = User::factory()->create();
    $user->assignRole('sales_managers');
    $quote = Quote::factory()->create(['sales_rep_id' => $user->id]);

    expect($user->can('viewAny', Quote::class))->toBeTrue();
    expect($user->can('view', $quote))->toBeTrue();
    expect($user->can('create', Quote::class))->toBeTrue();
    expect($user->can('update', $quote))->toBeTrue();
});

test('sales_reps can access quotes', function () {
    $user = User::factory()->create();
    $user->assignRole('sales_reps');
    $quote = Quote::factory()->create(['sales_rep_id' => $user->id]);

    expect($user->can('viewAny', Quote::class))->toBeTrue();
    expect($user->can('view', $quote))->toBeTrue();
    expect($user->can('create', Quote::class))->toBeTrue();
});

test('super_admins can access quotes', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admins');
    $quote = Quote::factory()->create(['sales_rep_id' => $user->id]);

    expect($user->can('viewAny', Quote::class))->toBeTrue();
    expect($user->can('view', $quote))->toBeTrue();
    expect($user->can('create', Quote::class))->toBeTrue();
    expect($user->can('update', $quote))->toBeTrue();
    expect($user->can('delete', $quote))->toBeTrue();
    expect($user->can('convertToOrder', $quote))->toBeTrue();
});

test('authorized users can convert quotes that are not already ordered', function () {
    $user = User::factory()->create();
    $user->assignRole('sales_reps');
    $quote = Quote::factory()->create(['sales_rep_id' => $user->id, 'status' => 'draft']);

    expect($user->can('convertToOrder', $quote))->toBeTrue();

    $quote->status = 'ordered';
    $quote->save();

    expect($user->can('convertToOrder', $quote))->toBeFalse();
});
