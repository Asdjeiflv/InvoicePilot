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
            // Use bccomp for precise decimal comparison (returns -1, 0, or 1)
            /** @var -1|0|1 $balanceCmp */
            $balanceCmp = bccomp((string) $invoice->balance_due, '0', 2);
            /** @var -1|0|1 $totalCmp */
            $totalCmp = bccomp((string) $totalPaid, (string) $invoice->total, 2);
            /** @var -1|0|1 $paidCmp */
            $paidCmp = bccomp((string) $totalPaid, '0', 2);

            if ($balanceCmp <= 0) {
                // Fully paid (balance_due <= 0)
                $invoice->status = 'paid';
            } elseif ($paidCmp > 0 && $totalCmp < 0) {
                // Partially paid (totalPaid > 0 AND totalPaid < total)
                $invoice->status = 'partial_paid';
            } elseif ($paidCmp === 0 && $balanceCmp > 0) {
                // No payments made, determine issued vs overdue
                if ($invoice->due_date && $invoice->due_date->isPast()) {
                    $invoice->status = 'overdue';
                } else {
                    $invoice->status = 'issued';
                }
            } elseif ($invoice->due_date && $invoice->due_date->isPast() && $balanceCmp > 0) {
                // Past due date with outstanding balance
                $invoice->status = 'overdue';
            } elseif ($invoice->status === 'overdue' && $invoice->due_date && !$invoice->due_date->isPast()) {
                // Due date was extended, revert from overdue
                $invoice->status = 'issued';
            }

            $invoice->save();

            return $invoice->fresh();
        });
    }
}
