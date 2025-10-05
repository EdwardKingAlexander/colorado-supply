<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Admin;

class CustomerPolicy
{
    public function viewAny(Admin $user): bool
    {
        return true; // All authenticated users can view customers
    }

    public function view(Admin $user, Customer $customer): bool
    {
        return true;
    }

    public function create(Admin $user): bool
    {
        return true; // All authenticated admins can create customers
    }

    public function update(Admin $user, Customer $customer): bool
    {
        // Admin and sales_manager can update any
        // sales_rep can update if they own it
        if (in_array($user->email, ['admin@example.com'])) {
            return true;
        }

        return $customer->owner_id === $user->id;
    }

    public function delete(Admin $user, Customer $customer): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }

    public function restore(Admin $user, Customer $customer): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }

    public function forceDelete(Admin $user, Customer $customer): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }
}
