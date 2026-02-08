<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    /**
     * Generate next quotation number
     * Format: Q-YYYY-00001
     */
    public function generateQuotationNumber(int $year = null): string
    {
        $year = $year ?? now()->year;
        $prefix = "Q-{$year}-";

        return DB::transaction(function () use ($prefix, $year) {
            // Get max number for this year with row lock
            $lastQuotation = Quotation::where('quotation_no', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(quotation_no, -5) AS UNSIGNED) DESC')
                ->first();

            if ($lastQuotation) {
                // Extract number and increment
                $lastNumber = (int) substr($lastQuotation->quotation_no, -5);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $quotationNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Double check uniqueness
            $attempts = 0;
            while (Quotation::where('quotation_no', $quotationNo)->exists() && $attempts < 10) {
                $nextNumber++;
                $quotationNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                throw new \RuntimeException('Failed to generate unique quotation number after 10 attempts');
            }

            return $quotationNo;
        });
    }

    /**
     * Generate next invoice number
     * Format: I-YYYY-00001
     */
    public function generateInvoiceNumber(int $year = null): string
    {
        $year = $year ?? now()->year;
        $prefix = "I-{$year}-";

        return DB::transaction(function () use ($prefix, $year) {
            // Get max number for this year with row lock
            $lastInvoice = Invoice::where('invoice_no', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(invoice_no, -5) AS UNSIGNED) DESC')
                ->first();

            if ($lastInvoice) {
                // Extract number and increment
                $lastNumber = (int) substr($lastInvoice->invoice_no, -5);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $invoiceNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Double check uniqueness
            $attempts = 0;
            while (Invoice::where('invoice_no', $invoiceNo)->exists() && $attempts < 10) {
                $nextNumber++;
                $invoiceNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                throw new \RuntimeException('Failed to generate unique invoice number after 10 attempts');
            }

            return $invoiceNo;
        });
    }
}
