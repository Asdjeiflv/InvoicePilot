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
        // Validate quotation status
        if ($quotation->status !== 'approved') {
            throw new \InvalidArgumentException('承認済みの見積のみ請求書に変換できます');
        }

        // Check if quotation already has invoices
        if ($quotation->invoices()->exists()) {
            throw new \InvalidArgumentException('この見積は既に請求書が作成されています');
        }

        // Validate quotation is not soft deleted
        if ($quotation->trashed()) {
            throw new \InvalidArgumentException('削除された見積は請求書に変換できません');
        }

        // Validate client exists and is not deleted
        $quotation->loadMissing('client');
        if (!$quotation->client || $quotation->client->trashed()) {
            throw new \InvalidArgumentException('取引先が存在しないか削除されています');
        }

        // Validate quotation has items
        if ($quotation->items->isEmpty()) {
            throw new \InvalidArgumentException('明細行が存在しない見積は請求書に変換できません');
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
