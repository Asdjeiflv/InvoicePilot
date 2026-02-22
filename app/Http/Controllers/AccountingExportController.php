<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingExportController extends Controller
{
    /**
     * Export invoices to freee-compatible CSV format.
     */
    public function exportFreee(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:invoices,payments',
        ]);

        $data = $validated['type'] === 'invoices'
            ? $this->getInvoiceData($validated['start_date'], $validated['end_date'])
            : $this->getPaymentData($validated['start_date'], $validated['end_date']);

        $csv = $this->generateFreeeCSV($data, $validated['type']);

        $filename = sprintf(
            'freee_export_%s_%s_to_%s.csv',
            $validated['type'],
            $validated['start_date'],
            $validated['end_date']
        );

        return Response::streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            foreach ($csv as $row) {
                echo implode(',', array_map([$this, 'escapeCsv'], $row)) . "\n";
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export to Money Forward-compatible CSV format.
     */
    public function exportMoneyForward(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:invoices,payments',
        ]);

        $data = $validated['type'] === 'invoices'
            ? $this->getInvoiceData($validated['start_date'], $validated['end_date'])
            : $this->getPaymentData($validated['start_date'], $validated['end_date']);

        $csv = $this->generateMoneyForwardCSV($data, $validated['type']);

        $filename = sprintf(
            'moneyforward_export_%s_%s_to_%s.csv',
            $validated['type'],
            $validated['start_date'],
            $validated['end_date']
        );

        return Response::streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            foreach ($csv as $row) {
                echo implode(',', array_map([$this, 'escapeCsv'], $row)) . "\n";
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Get invoice data for export.
     */
    private function getInvoiceData(string $startDate, string $endDate): array
    {
        return Invoice::with(['client', 'items'])
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'canceled'])
            ->orderBy('issued_at')
            ->get()
            ->toArray();
    }

    /**
     * Get payment data for export.
     */
    private function getPaymentData(string $startDate, string $endDate): array
    {
        return Payment::with(['invoice.client'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date')
            ->get()
            ->toArray();
    }

    /**
     * Generate freee-compatible CSV.
     */
    private function generateFreeeCSV(array $data, string $type): array
    {
        $csv = [];

        // Header row
        $csv[] = [
            '取引日',
            '借方勘定科目',
            '借方補助科目',
            '借方部門',
            '借方金額(税込)',
            '借方税区分',
            '貸方勘定科目',
            '貸方補助科目',
            '貸方部門',
            '貸方金額(税込)',
            '貸方税区分',
            '摘要',
            'タグ',
        ];

        if ($type === 'invoices') {
            foreach ($data as $invoice) {
                $csv[] = [
                    date('Y/m/d', strtotime($invoice['issued_at'])),
                    '売掛金', // 借方勘定科目
                    $invoice['client']['company_name'] ?? '', // 借方補助科目
                    '', // 借方部門
                    $invoice['total'], // 借方金額
                    '課税売上10%', // 借方税区分
                    '売上高', // 貸方勘定科目
                    '', // 貸方補助科目
                    '', // 貸方部門
                    $invoice['subtotal'], // 貸方金額（税抜）
                    '課税売上10%', // 貸方税区分
                    "請求書 {$invoice['invoice_no']}", // 摘要
                    'InvoicePilot', // タグ
                ];
            }
        } else {
            // Payments
            foreach ($data as $payment) {
                $csv[] = [
                    date('Y/m/d', strtotime($payment['payment_date'])),
                    '普通預金', // 借方勘定科目
                    '', // 借方補助科目
                    '', // 借方部門
                    $payment['amount'], // 借方金額
                    '対象外', // 借方税区分
                    '売掛金', // 貸方勘定科目
                    $payment['invoice']['client']['company_name'] ?? '', // 貸方補助科目
                    '', // 貸方部門
                    $payment['amount'], // 貸方金額
                    '対象外', // 貸方税区分
                    "入金 {$payment['invoice']['invoice_no']}", // 摘要
                    'InvoicePilot', // タグ
                ];
            }
        }

        return $csv;
    }

    /**
     * Generate Money Forward-compatible CSV.
     */
    private function generateMoneyForwardCSV(array $data, string $type): array
    {
        $csv = [];

        // Header row (Money Forward format)
        $csv[] = [
            '取引日',
            '借方勘定科目',
            '借方補助科目',
            '借方金額',
            '借方税区分',
            '貸方勘定科目',
            '貸方補助科目',
            '貸方金額',
            '貸方税区分',
            '摘要',
            'メモタグ',
        ];

        if ($type === 'invoices') {
            foreach ($data as $invoice) {
                $csv[] = [
                    date('Y-m-d', strtotime($invoice['issued_at'])),
                    '売掛金',
                    $invoice['client']['company_name'] ?? '',
                    $invoice['total'],
                    '課税売上10%',
                    '売上高',
                    '',
                    $invoice['subtotal'],
                    '課税売上10%',
                    "請求書 {$invoice['invoice_no']}",
                    'InvoicePilot',
                ];
            }
        } else {
            foreach ($data as $payment) {
                $csv[] = [
                    date('Y-m-d', strtotime($payment['payment_date'])),
                    '普通預金',
                    '',
                    $payment['amount'],
                    '対象外',
                    '売掛金',
                    $payment['invoice']['client']['company_name'] ?? '',
                    $payment['amount'],
                    '対象外',
                    "入金 {$payment['invoice']['invoice_no']}",
                    'InvoicePilot',
                ];
            }
        }

        return $csv;
    }

    /**
     * Escape CSV field.
     */
    private function escapeCsv(string|int|float|null $field): string
    {
        $field = (string) $field;

        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }

        return $field;
    }
}
