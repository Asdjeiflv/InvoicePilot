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
            // Get all quotations for this year with row lock
            $quotations = Quotation::where('quotation_no', 'like', "{$prefix}%")
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

            // Double check uniqueness (防御的プログラミング)
            $attempts = 0;
            while (Quotation::where('quotation_no', $quotationNo)->exists() && $attempts < 10) {
                $nextNumber++;
                $quotationNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                throw new \RuntimeException('見積番号の生成に失敗しました（10回試行後）');
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
            // Get all invoices for this year with row lock
            $invoices = Invoice::where('invoice_no', 'like', "{$prefix}%")
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

            // Double check uniqueness (防御的プログラミング)
            $attempts = 0;
            while (Invoice::where('invoice_no', $invoiceNo)->exists() && $attempts < 10) {
                $nextNumber++;
                $invoiceNo = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= 10) {
                throw new \RuntimeException('請求番号の生成に失敗しました（10回試行後）');
            }

            return $invoiceNo;
        });
    }
}
