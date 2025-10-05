<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\Admin;

class OpportunityPolicy
{
    public function viewAny(Admin $user): bool
    {
        return true;
    }

    public function view(Admin $user, Opportunity $opportunity): bool
    {
        return true;
    }

    public function create(Admin $user): bool
    {
        return true; // All users can create opportunities
    }

    public function update(Admin $user, Opportunity $opportunity): bool
    {
        // Admin and manager can update any
        if (in_array($user->email, ['admin@example.com']) || str_contains($user->email, 'manager')) {
            return true;
        }

        // Sales rep can only update their own
        return $opportunity->owner_id === $user->id;
    }

    public function delete(Admin $user, Opportunity $opportunity): bool
    {
        if (in_array($user->email, ['admin@example.com']) || str_contains($user->email, 'manager')) {
            return true;
        }

        return $opportunity->owner_id === $user->id;
    }

    public function restore(Admin $user, Opportunity $opportunity): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }

    public function forceDelete(Admin $user, Opportunity $opportunity): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }

    public function reassignOwner(Admin $user, Opportunity $opportunity): bool
    {
        // Only admin and sales_manager can reassign
        return in_array($user->email, ['admin@example.com']) ||
            str_contains($user->email, 'manager');
    }
}
