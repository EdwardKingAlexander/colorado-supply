<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    protected array $allowedRoles = [
        'admins',
        'sales_managers',
        'sales_reps',
        'super_admins',
    ];

    protected function hasAllowedRole(User|Admin $user): bool
    {
        return $user->hasAnyRole($this->allowedRoles);
    }

    public function viewAny(User|Admin $user): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function view(User|Admin $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function create(User|Admin $user): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function update(User|Admin $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function delete(User|Admin $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function restore(User|Admin $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function forceDelete(User|Admin $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function convertToOrder(User|Admin $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user) && $quote->status !== 'ordered';
    }
}
