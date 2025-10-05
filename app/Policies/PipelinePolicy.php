<?php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\User;

class PipelinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.pipelines.manage') || $user->can('crm.opportunities.viewAny');
    }

    public function view(User $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage') || $user->can('crm.opportunities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function update(User $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function delete(User $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function restore(User $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function forceDelete(User $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }
}
