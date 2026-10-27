<?php

use App\Filament\Resources\CRM\OpportunityResource\Pages\ListOpportunities;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(Admin::factory()->create(), 'admin');

    // The resource policies use the web guard's permission roles.
    $this->actingAs($this->user = User::factory()->create());
    $this->user->assignRole(Role::create(['name' => 'super_admin', 'guard_name' => 'web']));
});

it('shows a condensed mobile summary with customer, stage and close date', function () {
    $owner = $this->user;
    $pipeline = Pipeline::create(['name' => 'Federal', 'is_default' => true]);
    $stage = Stage::create(['pipeline_id' => $pipeline->id, 'name' => 'Proposal Sent', 'position' => 1]);

    $opportunity = Opportunity::create([
        'customer_id' => Customer::factory()->create(['name' => 'Acme Logistics'])->id,
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stage->id,
        'title' => 'Fort Carson Pallet Rack',
        'amount' => 125000,
        'expected_close_date' => '2026-11-15',
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
    ]);

    Livewire::test(ListOpportunities::class)
        ->assertCanSeeTableRecords([$opportunity])
        ->assertTableColumnExists('mobile_summary')
        ->assertTableColumnStateSet('mobile_summary', 'Fort Carson Pallet Rack', $opportunity)
        ->assertSee('Acme Logistics · Proposal Sent · Nov 15, 2026');
});
