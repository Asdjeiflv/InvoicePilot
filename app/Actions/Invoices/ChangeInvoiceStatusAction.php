<?php

namespace App\Actions\Invoices;

use App\Models\Invoice;

class ChangeInvoiceStatusAction
{
    private const ALLOWED_TRANSITIONS = [
        'draft' => ['issued', 'canceled'],
        'issued' => ['partial_paid', 'paid', 'overdue', 'canceled'],
        'partial_paid' => ['paid', 'overdue'],
        'overdue' => ['partial_paid', 'paid'],
        'paid' => [],
        'canceled' => [],
    ];

    public function execute(Invoice $invoice, string $newStatus): Invoice
    {
        $currentStatus = $invoice->status;

        if (!isset(self::ALLOWED_TRANSITIONS[$currentStatus])) {
            throw new \InvalidArgumentException("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus])) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$currentStatus} to {$newStatus}"
            );
        }

        $invoice->status = $newStatus;

        if ($newStatus === 'issued' && !$invoice->sent_at) {
            $invoice->sent_at = now();
        }

        $invoice->save();

        return $invoice;
    }
}
