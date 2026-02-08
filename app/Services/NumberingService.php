<?php

namespace App\Services;

use App\Exceptions\NumberGenerationException;
use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            // Get all quotations for this year with row lock (including soft-deleted)
            $quotations = Quotation::withTrashed()
                ->where('quotation_no', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->pluck('quotation_no');

            // Extract and find max number using regex
            $maxNumber = 0;
            foreach ($quotations as $quotationNo) {
                if (preg_match('/-(\d{5})$/', $quotationNo, $matches)) {
                    $number = (int) $matches[1];
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }

            $nextNumber = $maxNumber + 1;
            $quotationNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Double check uniqueness (防御的プログラミング) - include soft-deleted
            $attempts = 0;
            while (Quotation::withTrashed()->where('quotation_no', $quotationNo)->exists() && $attempts < 10) {
                $nextNumber++;
                $quotationNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                Log::error('Quotation number generation failed', [
                    'prefix' => $prefix,
                    'year' => $year,
                    'attempts' => $attempts,
                    'existing_count' => $quotations->count(),
                    'last_attempted' => $quotationNo,
                ]);

                throw NumberGenerationException::failedAfterAttempts('見積', $prefix, $attempts);
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
            // Get all invoices for this year with row lock (including soft-deleted)
            $invoices = Invoice::withTrashed()
                ->where('invoice_no', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->pluck('invoice_no');

            // Extract and find max number using regex
            $maxNumber = 0;
            foreach ($invoices as $invoiceNo) {
                if (preg_match('/-(\d{5})$/', $invoiceNo, $matches)) {
                    $number = (int) $matches[1];
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }

            $nextNumber = $maxNumber + 1;
            $invoiceNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Double check uniqueness (防御的プログラミング) - include soft-deleted
            $attempts = 0;
            while (Invoice::withTrashed()->where('invoice_no', $invoiceNo)->exists() && $attempts < 10) {
                $nextNumber++;
                $invoiceNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                Log::error('Invoice number generation failed', [
                    'prefix' => $prefix,
                    'year' => $year,
                    'attempts' => $attempts,
                    'existing_count' => $invoices->count(),
                    'last_attempted' => $invoiceNo,
                ]);

                throw NumberGenerationException::failedAfterAttempts('請求', $prefix, $attempts);
            }

            return $invoiceNo;
        });
    }
}
