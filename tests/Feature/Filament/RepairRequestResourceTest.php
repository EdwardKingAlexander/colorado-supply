<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RepairRequestResource\Pages\ListRepairRequests;
use App\Models\RepairRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepairRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        $user->assignRole($role);

        $this->actingAs($user);
    }

    public function test_admin_can_list_repair_requests(): void
    {
        $requests = collect([
            RepairRequest::create([
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'equipment_type' => 'Servo Motor',
                'model_number' => 'XYZ-99',
                'issue_description' => 'Not spinning up under load.',
            ]),
            RepairRequest::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'equipment_type' => 'PLC',
                'model_number' => 'ABC-1',
                'issue_description' => 'Will not power on.',
            ]),
        ]);

        Livewire::test(ListRepairRequests::class)
            ->assertCanSeeTableRecords($requests);
    }

    public function test_admin_can_mark_request_handled_and_unhandled(): void
    {
        $request = RepairRequest::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'equipment_type' => 'Servo Motor',
            'model_number' => 'XYZ-99',
            'issue_description' => 'Not spinning up under load.',
        ]);

        Livewire::test(ListRepairRequests::class)
            ->callTableAction('mark_handled', $request);

        $this->assertNotNull($request->fresh()->handled_at);

        Livewire::test(ListRepairRequests::class)
            ->callTableAction('mark_unhandled', $request);

        $this->assertNull($request->fresh()->handled_at);
    }
}
