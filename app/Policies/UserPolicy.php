<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class UserPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('users.viewAny');
    }

    public function view(Admin $admin, User $model): bool
    {
        return $admin->can('users.view');
    }

    public function create(Admin $admin): bool
    {
        return $admin->can('users.create');
    }

    public function update(Admin $admin, User $model): bool
    {
        return $admin->can('users.update');
    }

    public function delete(Admin $admin, User $model): bool
    {
        // Prevent deleting the last super_admin
        if ($model->hasRole('super_admin')) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return false;
            }
        }

        return $admin->can('users.delete');
    }

    public function restore(Admin $admin, User $model): bool
    {
        return $admin->can('users.delete');
    }

    public function forceDelete(Admin $admin, User $model): bool
    {
        return $admin->can('users.delete');
    }

    public function assignRoles(Admin $admin): bool
    {
        return $admin->can('users.assignRoles');
    }

    public function assignPermissions(Admin $admin): bool
    {
        return $admin->can('users.assignPermissions');
    }
}
