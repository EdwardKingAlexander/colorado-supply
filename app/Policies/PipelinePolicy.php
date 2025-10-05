<?php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\Admin;

class PipelinePolicy
{
    public function viewAny(Admin $user): bool
    {
        return true;
    }

    public function view(Admin $user, Pipeline $pipeline): bool
    {
        return true;
    }

    public function create(Admin $user): bool
    {
        // Only admin and sales_manager can create pipelines
        return in_array($user->email, ['admin@example.com']) ||
            str_contains($user->email, 'manager');
    }

    public function update(Admin $user, Pipeline $pipeline): bool
    {
        return in_array($user->email, ['admin@example.com']) ||
            str_contains($user->email, 'manager');
    }

    public function delete(Admin $user, Pipeline $pipeline): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }

    public function restore(Admin $user, Pipeline $pipeline): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }

    public function forceDelete(Admin $user, Pipeline $pipeline): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }
}
