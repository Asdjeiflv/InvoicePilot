<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Quotation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class QuotationObserver
{
    /**
     * Handle the Quotation "created" event.
     */
    public function created(Quotation $quotation): void
    {
        $this->log('created', $quotation, null, $quotation->getAttributes());
    }

    /**
     * Handle the Quotation "updated" event.
     */
    public function updated(Quotation $quotation): void
    {
        $changes = $quotation->getChanges();

        // updated_at のみの変更は監査ログ不要
        if (count($changes) === 1 && isset($changes['updated_at'])) {
            return;
        }

        $original = $quotation->getRawOriginal();
        $beforeData = Arr::only($original, array_keys($changes));

        $this->log('updated', $quotation, $beforeData, $changes);
    }

    /**
     * Handle the Quotation "deleted" event.
     */
    public function deleted(Quotation $quotation): void
    {
        $this->log('deleted', $quotation, $quotation->getAttributes(), null);
    }

    /**
     * Handle the Quotation "restored" event.
     */
    public function restored(Quotation $quotation): void
    {
        $this->log('restored', $quotation, null, $quotation->getAttributes());
    }

    /**
     * Log audit trail.
     */
    private function log(string $action, Quotation $quotation, ?array $before, ?array $after): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'target_type' => Quotation::class,
                'target_id' => $quotation->id,
                'before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for Quotation', [
                'quotation_id' => $quotation->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
