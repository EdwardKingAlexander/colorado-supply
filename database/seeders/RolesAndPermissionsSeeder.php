<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        Cache::forget('spatie.permission.cache');

        // Define all permissions
        $permissions = [
            // User Management
            'users.viewAny',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assignRoles',
            'users.assignPermissions',

            // CRM - Customers
            'crm.customers.viewAny',
            'crm.customers.view',
            'crm.customers.create',
            'crm.customers.update',
            'crm.customers.delete',

            // CRM - Opportunities
            'crm.opportunities.viewAny',
            'crm.opportunities.view',
            'crm.opportunities.create',
            'crm.opportunities.update',
            'crm.opportunities.delete',

            // CRM - Pipelines & Stages
            'crm.pipelines.manage',
            'crm.stages.manage',

            // CRM - Reports
            'crm.reports.view',

            // CRM - Activities & Attachments
            'crm.activities.manage',
            'crm.attachments.manage',
        ];

        // Create all permissions for 'web' guard
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions

        // Super Admin - All permissions
        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - All permissions except users.assignPermissions (optional restriction)
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all()); // Or exclude 'users.assignPermissions' if you want stricter control

        // Sales Manager - All CRM + reports + limited user management
        $salesManager = Role::create(['name' => 'sales_manager', 'guard_name' => 'web']);
        $salesManager->givePermissionTo([
            // CRM Full Access
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

            // Limited User Management
            'users.viewAny',
            'users.view',
            'users.update', // Can update users but not create/delete
        ]);

        // Sales Rep - CRUD owned opportunities, view others, manage activities/attachments
        $salesRep = Role::create(['name' => 'sales_rep', 'guard_name' => 'web']);
        $salesRep->givePermissionTo([
            // Customers
            'crm.customers.viewAny',
            'crm.customers.view',
            'crm.customers.create',
            'crm.customers.update',

            // Opportunities (policy will handle "owned" logic)
            'crm.opportunities.viewAny',
            'crm.opportunities.view',
            'crm.opportunities.create',
            'crm.opportunities.update',
            'crm.opportunities.delete', // Can delete own

            // Activities & Attachments
            'crm.activities.manage',
            'crm.attachments.manage',
        ]);

        // Viewer - Read-only CRM access
        $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo([
            'crm.customers.viewAny',
            'crm.customers.view',
            'crm.opportunities.viewAny',
            'crm.opportunities.view',
            'crm.reports.view',
        ]);

        // Assign super_admin role to first user if exists
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('super_admin');
            $this->command->info("Assigned super_admin role to user: {$firstUser->email}");
        } else {
            $this->command->warn('No users found. Super admin role not assigned.');
        }

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
