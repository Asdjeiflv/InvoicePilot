<?php

namespace App\Actions\Invoices;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class RecalculateInvoiceBalanceAction
{
    public function execute(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            // Sum all payments
            $totalPaid = $invoice->payments()->sum('amount');

            // Update invoice amounts
            $invoice->paid_amount = $totalPaid;
            $invoice->balance_due = $invoice->total - $totalPaid;

            // Don't change status for canceled or draft invoices
            if (in_array($invoice->status, ['canceled', 'draft'])) {
                $invoice->save();
                return $invoice->fresh();
            }

            // Update status based on balance and due date
            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
            } elseif ($totalPaid > 0 && $totalPaid < $invoice->total) {
                $invoice->status = 'partial_paid';
            } elseif ($totalPaid == 0 && $invoice->balance_due > 0) {
                // No payments, revert to issued or overdue based on due date
                if ($invoice->due_date && $invoice->due_date->isPast()) {
                    $invoice->status = 'overdue';
                } else {
                    $invoice->status = 'issued';
                }
            } elseif ($invoice->due_date && $invoice->due_date->isPast() && $invoice->balance_due > 0) {
                // Only set to overdue if there's an unpaid balance and past due date
                $invoice->status = 'overdue';
            } elseif ($invoice->status === 'overdue' && $invoice->due_date && !$invoice->due_date->isPast()) {
                // Revert from overdue if due date was extended
                $invoice->status = 'issued';
            }

            $invoice->save();

            return $invoice->fresh();
        });
    }
}
