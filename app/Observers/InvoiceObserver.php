<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Invoice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $this->log('created', $invoice, null, $invoice->getAttributes());
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $changes = $invoice->getChanges();

        // updated_at のみの変更は監査ログ不要
        if (count($changes) === 1 && isset($changes['updated_at'])) {
            return;
        }

        $original = $invoice->getRawOriginal();
        $beforeData = Arr::only($original, array_keys($changes));

        $this->log('updated', $invoice, $beforeData, $changes);
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        $this->log('deleted', $invoice, $invoice->getAttributes(), null);
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        $this->log('restored', $invoice, null, $invoice->getAttributes());
    }

    /**
     * Log audit trail.
     */
    private function log(string $action, Invoice $invoice, ?array $before, ?array $after): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'target_type' => Invoice::class,
                'target_id' => $invoice->id,
                'before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for Invoice', [
                'invoice_id' => $invoice->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
