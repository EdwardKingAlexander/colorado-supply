# Production Deployment Guide - RBAC & CRM Features

## Prerequisites
SSH into your Laravel Forge server and navigate to the application directory:
```bash
cd /home/forge/cogovsupply.com
```

## Step 1: Run Migrations
This will create the permission tables and any other new database tables:

```bash
php artisan migrate --force
```

**Expected Output:** You should see migrations for:
- `create_permission_tables` (roles, permissions, model_has_roles, etc.)
- Any CRM-related tables if not already migrated

## Step 2: Seed Roles and Permissions
This creates the 5 roles and all permissions:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

**Expected Output:**
- "Assigned super_admin role to admin: [email]"
- "Roles and permissions seeded successfully!"

This will create:
- **Roles:** super_admin, admin, sales_manager, sales_rep, viewer
- **Permissions:** 25+ permissions for users and CRM modules
- Assigns super_admin role to the first admin user

## Step 3: Clear All Caches
Clear all application caches to ensure new features are loaded:

```bash
php artisan optimize:clear
```

This clears:
- Configuration cache
- Route cache
- View cache
- Application cache
- Permission cache

## Step 4: Rebuild Caches for Production
Optimize the application for production performance:

```bash
php artisan optimize
```

This will:
- Cache configuration
- Cache routes
- Cache views
- Precompile views

## Step 5: Verify Installation

### Check Spatie Permission Installation
```bash
php artisan about
```

Look for "Spatie Permissions" section showing version 6.21.0

### Verify Roles Were Created
```bash
php artisan tinker
```

Then run:
```php
\Spatie\Permission\Models\Role::all()->pluck('name');
// Should show: super_admin, admin, sales_manager, sales_rep, viewer

\Spatie\Permission\Models\Permission::count();
// Should show: 25+ permissions

exit
```

### Check Your Admin User Has Super Admin Role
```bash
php artisan tinker
```

Then run:
```php
$admin = \App\Models\Admin::first();
$admin->getRoleNames();
// Should show: ["super_admin"]

exit
```

## Step 6: Verify in Browser
1. Log out and log back into the admin panel
2. You should now see:
   - **Admin** navigation group with "Users" menu item (if you have users.viewAny permission)
   - **CRM** navigation group with Customers, Opportunities, Pipelines, Stages
   - All CRM features should be accessible

## Troubleshooting

### If features still don't show:

1. **Clear browser cache** - Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)

2. **Verify your admin has the super_admin role:**
   ```bash
   php artisan tinker
   $admin = \App\Models\Admin::where('email', 'YOUR_EMAIL')->first();
   $admin->assignRole('super_admin');
   exit
   ```

3. **Clear permission cache manually:**
   ```bash
   php artisan cache:forget spatie.permission.cache
   php artisan optimize:clear
   ```

4. **Check error logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Verify migrations ran:**
   ```bash
   php artisan migrate:status
   ```

### If you get "Permission denied" errors:

Run this to give your admin user super_admin access:
```bash
php artisan tinker
\App\Models\Admin::first()->assignRole('super_admin');
exit
php artisan cache:forget spatie.permission.cache
```

## Expected Features After Deployment

### Navigation Menu Items:
- **Admin** (navigation group)
  - Users (if you have users.viewAny permission)

- **CRM** (navigation group)
  - Customers
  - Opportunities
  - Pipelines
  - Stages

### User Resource Features:
- View all users
- Create/edit users with role assignment
- Multi-select roles (super_admin, admin, sales_manager, sales_rep, viewer)
- Multi-select direct permissions

### CRM Features with Permissions:
- **Super Admin & Admin:** Full access to everything
- **Sales Manager:** Full CRM access + limited user management
- **Sales Rep:** Can only edit their own opportunities/customers
- **Viewer:** Read-only access to CRM data

## Rolling Back (Emergency Only)

If something goes wrong and you need to rollback:

```bash
# Rollback the last migration batch
php artisan migrate:rollback --step=1 --force

# Clear all caches
php artisan optimize:clear
```

## Post-Deployment Checklist

- [ ] Migrations completed successfully
- [ ] Seeder ran without errors
- [ ] Super admin role assigned to your admin user
- [ ] All caches cleared
- [ ] Can see Admin navigation group
- [ ] Can see Users menu item
- [ ] Can see CRM navigation group
- [ ] Can access CRM resources (Customers, Opportunities, etc.)
- [ ] Permissions are enforcing correctly (test with different roles)

## Support

If you encounter issues not covered here:
1. Check `/storage/logs/laravel.log` for detailed error messages
2. Run `php artisan about` to verify package installation
3. Verify database tables exist: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
