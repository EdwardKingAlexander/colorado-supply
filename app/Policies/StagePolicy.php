<?php

namespace App\Policies;

use App\Models\Stage;
use App\Models\Admin;

class StagePolicy
{
    public function viewAny(Admin $user): bool
    {
        return true;
    }

    public function view(Admin $user, Stage $stage): bool
    {
        return true;
    }

    public function create(Admin $user): bool
    {
        return in_array($user->email, ['admin@example.com']) ||
            str_contains($user->email, 'manager');
    }

    public function update(Admin $user, Stage $stage): bool
    {
        return in_array($user->email, ['admin@example.com']) ||
            str_contains($user->email, 'manager');
    }

    public function delete(Admin $user, Stage $stage): bool
    {
        return in_array($user->email, ['admin@example.com']) ||
            str_contains($user->email, 'manager');
    }

    public function restore(Admin $user, Stage $stage): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }

    public function forceDelete(Admin $user, Stage $stage): bool
    {
        return in_array($user->email, ['admin@example.com']);
    }
}
