<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\ContractDocument;
use App\Models\User;

class ContractDocumentPolicy
{
    /**
     * Determine if the user can view any contract documents.
     */
    public function viewAny(User|Admin $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager', 'contracts_analyst']);
    }

    /**
     * Determine if the user can view the contract document.
     */
    public function view(User|Admin $user, ContractDocument $document): bool
    {
        // Super admins can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // CUI documents require elevated access
        if ($document->cui_detected) {
            return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager']);
        }

        // Regular documents accessible to all contract roles
        return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager', 'contracts_analyst']);
    }

    /**
     * Determine if the user can create contract documents.
     */
    public function create(User|Admin $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager', 'contracts_analyst']);
    }

    /**
     * Determine if the user can update the contract document.
     */
    public function update(User|Admin $user, ContractDocument $document): bool
    {
        // Super admins and admins can update all
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Contracts managers can update any document
        if ($user->hasRole('contracts_manager')) {
            return true;
        }

        // Analysts can only update documents they uploaded
        if ($user->hasRole('contracts_analyst')) {
            return $document->uploaded_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the contract document.
     */
    public function delete(User|Admin $user, ContractDocument $document): bool
    {
        // Only super admins and admins can delete
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            return false;
        }

        // Cannot delete parsed documents that are in use
        // (Future: check if document has extracted facts being used)
        return true;
    }

    /**
     * Determine if the user can restore the contract document.
     */
    public function restore(User|Admin $user, ContractDocument $document): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine if the user can permanently delete the contract document.
     */
    public function forceDelete(User|Admin $user, ContractDocument $document): bool
    {
        // Only super admins can permanently delete
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can view CUI-marked documents.
     */
    public function viewCui(User|Admin $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager']);
    }

    /**
     * Determine if the user can download the contract document.
     */
    public function download(User|Admin $user, ContractDocument $document): bool
    {
        // Same as view permissions
        return $this->view($user, $document);
    }

    /**
     * Determine if the user can link documents (create relationships).
     */
    public function linkDocuments(User|Admin $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager']);
    }

    /**
     * Determine if the user can trigger document parsing.
     */
    public function parse(User|Admin $user, ContractDocument $document): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager', 'contracts_analyst']);
    }

    /**
     * Determine if the user can review extracted facts.
     */
    public function review(User|Admin $user, ContractDocument $document): bool
    {
        // CUI documents require elevated access for review
        if ($document->cui_detected) {
            return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager']);
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'contracts_manager', 'contracts_analyst']);
    }
}
