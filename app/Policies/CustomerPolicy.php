<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        return $user->can('crm.customers.viewAny');
    }

    public function view(User|Admin $user, Customer $customer): bool
    {
        return $user->can('crm.customers.view');
    }

    public function create(User|Admin $user): bool
    {
        return $user->can('crm.customers.create');
    }

    public function update(User|Admin $user, Customer $customer): bool
    {
        // Check permission first
        if (! $user->can('crm.customers.update')) {
            return false;
        }

        // Admins and managers can update any
        // Sales reps can only update if they own it
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        return $customer->owner_id === $user->id;
    }

    public function delete(User|Admin $user, Customer $customer): bool
    {
        return $user->can('crm.customers.delete');
    }

    public function restore(User|Admin $user, Customer $customer): bool
    {
        return $user->can('crm.customers.delete');
    }

    public function forceDelete(User|Admin $user, Customer $customer): bool
    {
        return $user->can('crm.customers.delete');
    }
}
