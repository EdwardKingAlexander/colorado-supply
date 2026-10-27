<?php

namespace Tests\Feature\Filament;

use App\Enums\OrderStatus;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLog;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogResourceTest extends TestCase
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

    public function test_admin_can_list_activity_log_entries(): void
    {
        $order = Order::factory()->create();
        $order->update(['status' => OrderStatus::Confirmed]);

        $entries = ActivityLog::query()
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);

        Livewire::test(ListActivityLog::class)
            ->assertCanSeeTableRecords($entries);
    }

    public function test_subject_type_filter_narrows_to_matching_entries(): void
    {
        $order = Order::factory()->create();
        $vendor = Vendor::factory()->create(['cage_code' => 'ABC12']);

        $orderEntries = ActivityLog::query()
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->get();
        $vendorEntries = ActivityLog::query()
            ->where('subject_type', Vendor::class)
            ->where('subject_id', $vendor->id)
            ->get();

        $this->assertNotEmpty($orderEntries);
        $this->assertNotEmpty($vendorEntries);

        Livewire::test(ListActivityLog::class)
            ->filterTable('subject_type', Order::class)
            ->assertCanSeeTableRecords($orderEntries)
            ->assertCanNotSeeTableRecords($vendorEntries);
    }

    public function test_admin_can_view_entry_detail(): void
    {
        $order = Order::factory()->create();
        $order->update(['status' => OrderStatus::Confirmed]);

        $entry = ActivityLog::query()
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('event', 'updated')
            ->firstOrFail();

        Livewire::test(ViewActivityLog::class, ['record' => $entry->getKey()])
            ->assertSuccessful()
            ->assertSee($entry->description);
    }

    public function test_resource_exposes_no_create_edit_or_delete_pages(): void
    {
        $this->assertSame(['index', 'view'], array_keys(ActivityLogResource::getPages()));
        $this->assertFalse(ActivityLogResource::canCreate());
    }

    public function test_subject_url_links_known_subjects_and_nulls_the_rest(): void
    {
        // Filament resolves per-record permissions through the panel's
        // (admin) guard, so this test authenticates one too.
        $admin = Admin::factory()->create();
        $admin->assignRole(Role::query()->firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'super_admin', 'guard_name' => 'web']
        ));
        $this->actingAs($admin, 'admin');

        $order = Order::factory()->create();
        $orderEntry = ActivityLog::query()
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->firstOrFail();

        $this->assertStringContainsString("/admin/c-r-m/orders/{$order->getKey()}", ActivityLogResource::subjectUrl($orderEntry));

        $vendor = Vendor::factory()->create(['cage_code' => 'ABC12']);
        $vendorEntry = ActivityLog::query()
            ->where('subject_type', Vendor::class)
            ->where('subject_id', $vendor->id)
            ->firstOrFail();

        $this->assertStringContainsString(
            "/admin/vendors/{$vendor->getKey()}/edit",
            ActivityLogResource::subjectUrl($vendorEntry)
        );

        $unknown = ActivityLog::create([
            'log_name' => 'default',
            'description' => 'mystery',
            'subject_type' => 'App\\Models\\DoesNotExist',
            'subject_id' => 1,
            'event' => 'created',
        ]);

        $this->assertNull(ActivityLogResource::subjectUrl($unknown));

        $order->forceDelete();

        $this->assertNull(ActivityLogResource::subjectUrl($orderEntry->fresh()));
    }
}
