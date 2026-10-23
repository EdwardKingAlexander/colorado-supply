<?php

use App\Filament\Resources\ReportResource\Pages\ListReports;
use App\Models\Admin;
use App\Models\Report;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('lists customer reports read-only for admins', function () {
    $reports = Report::factory()->count(2)->create();

    $admin = Admin::factory()->create();
    $admin->assignRole(Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
    $this->actingAs($admin, 'admin');

    Livewire::test(ListReports::class)
        ->assertOk()
        ->assertCanSeeTableRecords($reports)
        ->searchTable($reports->first()->name)
        ->assertCanSeeTableRecords([$reports->first()]);
});
