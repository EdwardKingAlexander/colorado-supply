<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.viewAny');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        // Prevent deleting yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Prevent deleting the last super_admin
        if ($model->hasRole('super_admin')) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return false;
            }
        }

        return $user->can('users.delete');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('users.delete');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('users.delete');
    }

    public function assignRoles(User $user): bool
    {
        return $user->can('users.assignRoles');
    }

    public function assignPermissions(User $user): bool
    {
        return $user->can('users.assignPermissions');
    }
}
