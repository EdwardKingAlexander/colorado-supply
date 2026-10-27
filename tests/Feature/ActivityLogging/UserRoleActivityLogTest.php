<?php

namespace Tests\Feature\ActivityLogging;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_a_role_logs_the_grant_with_the_admin_causer(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();
        Role::create(['name' => 'sales-rep', 'guard_name' => 'web']);

        $user->assignRole('sales-rep');

        $entry = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'role_assigned')
            ->firstOrFail();

        $this->assertSame('users', $entry->log_name);
        $this->assertTrue($entry->causer->is($admin));
        $this->assertSame(['sales-rep'], $entry->properties['names']);
        $this->assertStringContainsString("'sales-rep'", $entry->description);
    }

    public function test_removing_a_role_logs_the_removal(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();
        Role::create(['name' => 'sales-rep', 'guard_name' => 'web']);
        $user->assignRole('sales-rep');

        $user->removeRole('sales-rep');

        $entry = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'role_removed')
            ->firstOrFail();

        $this->assertSame('users', $entry->log_name);
        $this->assertTrue($entry->causer->is($admin));
        $this->assertSame(['sales-rep'], $entry->properties['names']);
    }

    public function test_granting_a_direct_permission_is_logged(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();
        Permission::create(['name' => 'orders.refund', 'guard_name' => 'web']);

        $user->givePermissionTo('orders.refund');

        $entry = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'permission_granted')
            ->firstOrFail();

        $this->assertSame('users', $entry->log_name);
        $this->assertSame(['orders.refund'], $entry->properties['names']);
    }

    public function test_assigning_a_role_to_an_admin_uses_the_admins_log(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $target = Admin::factory()->create();
        Role::create(['name' => 'support', 'guard_name' => 'web']);

        $target->assignRole('support');

        $entry = ActivityLog::query()
            ->where('subject_type', Admin::class)
            ->where('subject_id', $target->id)
            ->where('event', 'role_assigned')
            ->firstOrFail();

        $this->assertSame('admins', $entry->log_name);
        $this->assertSame(['support'], $entry->properties['names']);
    }
}
