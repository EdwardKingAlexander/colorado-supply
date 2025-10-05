<?php

namespace App\Policies;

use App\Models\Stage;
use App\Models\User;

class StagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.stages.manage') || $user->can('crm.opportunities.viewAny');
    }

    public function view(User $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage') || $user->can('crm.opportunities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function update(User $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function delete(User $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function restore(User $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }

    public function forceDelete(User $user, Stage $stage): bool
    {
        return $user->can('crm.stages.manage');
    }
}
