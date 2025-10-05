<?php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\Admin;

class PipelinePolicy
{
    public function viewAny(Admin $user): bool
    {
        return $user->can('crm.pipelines.manage') || $user->can('crm.opportunities.viewAny');
    }

    public function view(Admin $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage') || $user->can('crm.opportunities.view');
    }

    public function create(Admin $user): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function update(Admin $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function delete(Admin $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function restore(Admin $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }

    public function forceDelete(Admin $user, Pipeline $pipeline): bool
    {
        return $user->can('crm.pipelines.manage');
    }
}
