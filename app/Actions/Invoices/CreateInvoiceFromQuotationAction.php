<?php

namespace App\Actions\Invoices;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\NumberingService;
use Illuminate\Support\Facades\DB;

class CreateInvoiceFromQuotationAction
{
    public function __construct(
        private NumberingService $numberingService
    ) {}

    public function execute(Quotation $quotation, array $data = []): Invoice
    {
        if ($quotation->status !== 'approved') {
            throw new \RuntimeException('Only approved quotations can be converted to invoices');
        }

        return DB::transaction(function () use ($quotation, $data) {
            // Generate invoice number
            $invoiceNo = $this->numberingService->generateInvoiceNumber();

            // Create invoice
            $invoice = Invoice::create([
                'invoice_no' => $invoiceNo,
                'client_id' => $quotation->client_id,
                'quotation_id' => $quotation->id,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                'subtotal' => $quotation->subtotal,
                'tax_total' => $quotation->tax_total,
                'total' => $quotation->total,
                'paid_amount' => 0,
                'balance_due' => $quotation->total,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Copy items
            foreach ($quotation->items as $quotationItem) {
                $invoice->items()->create([
                    'description' => $quotationItem->description,
                    'quantity' => $quotationItem->quantity,
                    'unit_price' => $quotationItem->unit_price,
                    'tax_rate' => $quotationItem->tax_rate,
                    'line_total' => $quotationItem->line_total,
                ]);
            }

            return $invoice->fresh(['items', 'client']);
        });
    }
}
