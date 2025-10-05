<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\Admin;

class OpportunityPolicy
{
    public function viewAny(Admin $user): bool
    {
        return $user->can('crm.opportunities.viewAny');
    }

    public function view(Admin $user, Opportunity $opportunity): bool
    {
        return $user->can('crm.opportunities.view');
    }

    public function create(Admin $user): bool
    {
        return $user->can('crm.opportunities.create');
    }

    public function update(Admin $user, Opportunity $opportunity): bool
    {
        // Check permission first
        if (!$user->can('crm.opportunities.update')) {
            return false;
        }

        // Admins and managers can update any
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        // Sales reps can only update their own
        return $opportunity->owner_id === $user->id;
    }

    public function delete(Admin $user, Opportunity $opportunity): bool
    {
        // Check permission first
        if (!$user->can('crm.opportunities.delete')) {
            return false;
        }

        // Admins and managers can delete any
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        // Sales reps can only delete their own
        return $opportunity->owner_id === $user->id;
    }

    public function restore(Admin $user, Opportunity $opportunity): bool
    {
        return $user->can('crm.opportunities.delete');
    }

    public function forceDelete(Admin $user, Opportunity $opportunity): bool
    {
        return $user->can('crm.opportunities.delete');
    }

    public function reassignOwner(Admin $user, Opportunity $opportunity): bool
    {
        // Only admin and sales_manager can reassign
        return $user->hasAnyRole(['super_admin', 'admin', 'sales_manager']);
    }
}
