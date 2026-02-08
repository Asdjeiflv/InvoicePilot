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
            
            // Update invoice
            $invoice->paid_amount = $totalPaid;
            $invoice->balance_due = $invoice->total - $totalPaid;
            
            // Update status based on balance
            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
            } elseif ($totalPaid > 0) {
                $invoice->status = 'partial_paid';
            } elseif ($invoice->status !== 'draft' && $invoice->due_date->isPast()) {
                $invoice->status = 'overdue';
            }
            
            $invoice->save();
            
            return $invoice->fresh();
        });
    }
}
