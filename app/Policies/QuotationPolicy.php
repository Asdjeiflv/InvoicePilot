<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuotationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view quotations
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quotation $quotation): bool
    {
        // All authenticated users can view quotations
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and sales can create quotations
        return $user->hasAnyRole(['admin', 'sales']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quotation $quotation): bool
    {
        // Auditors cannot update
        if ($user->isAuditor()) {
            return false;
        }

        // Admin can always update
        if ($user->isAdmin()) {
            return true;
        }

        // Sales can only update draft quotations
        if ($user->isSales() && $quotation->status === 'draft') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quotation $quotation): bool
    {
        // Only admin can delete, and only draft quotations
        return $user->isAdmin() && $quotation->status === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Quotation $quotation): bool
    {
        // Only admin can restore
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Quotation $quotation): bool
    {
        // Only admin can force delete
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can approve a quotation.
     */
    public function approve(User $user, Quotation $quotation): bool
    {
        // Only admin can approve
        // Quotation must be in draft or submitted status
        return $user->isAdmin() && in_array($quotation->status, ['draft', 'submitted']);
    }

    /**
     * Determine whether the user can reject a quotation.
     */
    public function reject(User $user, Quotation $quotation): bool
    {
        // Only admin can reject
        return $user->isAdmin();
    }
}
