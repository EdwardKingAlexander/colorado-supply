<?php

namespace App\Policies;

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

    protected function hasAllowedRole(User $user): bool
    {
        return $user->hasAnyRole($this->allowedRoles);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function view(User $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function create(User $user): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function update(User $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function restore(User $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function forceDelete(User $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user);
    }

    public function convertToOrder(User $user, Quote $quote): bool
    {
        return $this->hasAllowedRole($user) && $quote->status !== 'ordered';
    }
}
