<?php

namespace App\Policies;

use App\Models\Stage;
use App\Models\Admin;

class StagePolicy
{
    public function viewAny(Admin $user): bool
    {
        return $user->can('crm.stages.manage') || $user->can('crm.opportunities.viewAny');
    }

    public function view(Admin $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage') || $user->can('crm.opportunities.view');
    }

    public function create(Admin $user): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function update(Admin $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function delete(Admin $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function restore(Admin $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function forceDelete(Admin $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }
}
