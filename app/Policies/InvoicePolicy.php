<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (including auditor) can view invoices
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // All authenticated users (including auditor) can view invoices
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and sales can create invoices
        // Auditors and accounting cannot create
        return $user->hasAnyRole(['admin', 'sales']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Auditors cannot update
        if ($user->isAuditor()) {
            return false;
        }

        // Admin can always update
        if ($user->isAdmin()) {
            return true;
        }

        // Sales can only update draft invoices
        if ($user->isSales() && $invoice->status === 'draft') {
            return true;
        }

        // Accounting can update issued/partial_paid/overdue invoices
        if ($user->isAccounting() && in_array($invoice->status, ['issued', 'partial_paid', 'overdue'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only admin can delete, and only draft invoices
        return $user->isAdmin() && $invoice->status === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // Only admin can restore
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // Only admin can force delete
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can issue an invoice.
     */
    public function issue(User $user, Invoice $invoice): bool
    {
        // Auditors cannot issue
        if ($user->isAuditor()) {
            return false;
        }

        // Only draft invoices can be issued
        if ($invoice->status !== 'draft') {
            return false;
        }

        // Admin, accounting, and sales can issue
        return $user->hasAnyRole(['admin', 'accounting', 'sales']);
    }

    /**
     * Determine whether the user can cancel an invoice.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        // Only admin and accounting can cancel
        return $user->hasAnyRole(['admin', 'accounting']);
    }
}
