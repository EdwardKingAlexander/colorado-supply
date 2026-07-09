<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User|Admin $admin): bool
    {
        return $admin->can('users.viewAny');
    }

    public function view(User|Admin $admin, User $model): bool
    {
        return $admin->can('users.view');
    }

    public function create(User|Admin $admin): bool
    {
        return $admin->can('users.create');
    }

    public function update(User|Admin $admin, User $model): bool
    {
        return $admin->can('users.update');
    }

    public function delete(User|Admin $admin, User $model): bool
    {
        // Prevent deleting yourself
        if ($admin::class === $model::class && $admin->id === $model->id) {
            return false;
        }

        // Prevent deleting the last super_admin
        if ($model->hasRole('super_admin')) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return false;
            }
        }

        return $admin->can('users.delete');
    }

    public function restore(User|Admin $admin, User $model): bool
    {
        return $admin->can('users.delete');
    }

    public function forceDelete(User|Admin $admin, User $model): bool
    {
        return $admin->can('users.delete');
    }

    public function assignRoles(User|Admin $admin): bool
    {
        return $admin->can('users.assignRoles');
    }

    public function assignPermissions(User|Admin $admin): bool
    {
        return $admin->can('users.assignPermissions');
    }
}
