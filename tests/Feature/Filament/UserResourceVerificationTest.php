<?php

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Admin;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    // The admin panel authenticates via the `admin` guard, while the
    // resource's ability checks (auth()->user()->can(...)) resolve the
    // default web guard — sign in on both.
    $this->actingAs(Admin::factory()->create(), 'admin');
    $this->actingAs($this->user = User::factory()->create());

    $role = Role::query()->firstOrCreate(
        ['name' => 'super_admin', 'guard_name' => 'web'],
        ['name' => 'super_admin', 'guard_name' => 'web']
    );

    $this->user->assignRole($role);
});

it('shows the verified column for users', function () {
    $unverified = User::factory()->unverified()->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$unverified, $this->user])
        ->assertTableColumnExists('email_verified_at');
});

it('marks an unverified user as verified via the row action', function () {
    $unverified = User::factory()->unverified()->create();

    Livewire::test(ListUsers::class)
        ->callTableAction('markVerified', $unverified);

    expect($unverified->fresh()->email_verified_at)->not->toBeNull();
});

it('hides the mark-verified action for already verified users', function () {
    $verified = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('markVerified', $verified);
});
