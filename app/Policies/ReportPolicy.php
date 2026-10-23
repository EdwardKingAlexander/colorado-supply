<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Report;
use App\Models\User;

/**
 * Company-wide access with no roles (owner decision 2026-10-23): owners do
 * everything; colleagues see reports shared with the company and edit them
 * only when the owner allowed it. Sharing and deleting stay with the owner.
 * Admins may look (support) but never change a customer's report.
 */
class ReportPolicy
{
    public function viewAny(User|Admin $user): bool
    {
        return true;
    }

    public function view(User|Admin $user, Report $report): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $this->owns($user, $report)
            || ($report->isSharedWithCompany() && $user->company_id !== null && (int) $report->company_id === (int) $user->company_id);
    }

    public function create(User|Admin $user): bool
    {
        return $user instanceof User;
    }

    public function update(User|Admin $user, Report $report): bool
    {
        return $user instanceof User
            && ($this->owns($user, $report) || ($report->company_can_edit && $this->view($user, $report)));
    }

    /** Change who the report is shared with and whether they may edit it. */
    public function share(User|Admin $user, Report $report): bool
    {
        return $user instanceof User && $this->owns($user, $report);
    }

    public function delete(User|Admin $user, Report $report): bool
    {
        return $user instanceof User && $this->owns($user, $report);
    }

    public function duplicate(User|Admin $user, Report $report): bool
    {
        return $user instanceof User && $this->view($user, $report);
    }

    private function owns(User $user, Report $report): bool
    {
        return (int) $report->user_id === (int) $user->id;
    }
}
