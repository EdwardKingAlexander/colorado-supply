<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Location;
use App\Models\User;
use App\Services\Organizations\CompanyDomainService;

class LocationPolicy
{
    public function __construct(private readonly CompanyDomainService $domains) {}

    public function viewAny(User|Admin $user): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $this->eligibleCompanyUser($user);
    }

    public function view(User|Admin $user, Location $location): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $this->eligibleCompanyUser($user)
            && (int) $location->company_id === (int) $user->company_id;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, Location $location): bool
    {
        return $this->view($user, $location);
    }

    public function delete(User|Admin $user, Location $location): bool
    {
        return $this->view($user, $location);
    }

    public function restore(User|Admin $user, Location $location): bool
    {
        return $this->view($user, $location);
    }

    private function eligibleCompanyUser(User $user): bool
    {
        $company = $user->company;

        return $company !== null
            && $company->enforcesEmailDomains()
            && $this->domains->matches($company, $user->email);
    }
}
