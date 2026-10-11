<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\NsnSourcingCase;
use App\Models\User;
use App\Services\NsnSourcing\SourcingContext;

/**
 * Sourcing cases and everything under them (verifications, contacts,
 * inquiries, responses, files) are authorized through the case's company.
 * Admins act for the home company; users only for their own company, and
 * not at all without one.
 */
class NsnSourcingCasePolicy
{
    public function viewAny(User|Admin $user): bool
    {
        return SourcingContext::companyId($user) !== null;
    }

    public function view(User|Admin $user, NsnSourcingCase $case): bool
    {
        $companyId = SourcingContext::companyId($user);

        return $companyId !== null && (int) $case->company_id === $companyId;
    }

    public function create(User|Admin $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User|Admin $user, NsnSourcingCase $case): bool
    {
        return $this->view($user, $case);
    }

    public function delete(User|Admin $user, NsnSourcingCase $case): bool
    {
        return false;
    }

    public function deleteAny(User|Admin $user): bool
    {
        return false;
    }
}
