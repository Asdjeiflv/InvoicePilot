<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->log('created', $payment, null, $payment->getAttributes());
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $changes = $payment->getChanges();

        // updated_at のみの変更は監査ログ不要
        if (count($changes) === 1 && isset($changes['updated_at'])) {
            return;
        }

        $original = $payment->getRawOriginal();
        $beforeData = Arr::only($original, array_keys($changes));

        $this->log('updated', $payment, $beforeData, $changes);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->log('deleted', $payment, $payment->getAttributes(), null);
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->log('restored', $payment, null, $payment->getAttributes());
    }

    /**
     * Log audit trail.
     */
    private function log(string $action, Payment $payment, ?array $before, ?array $after): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'target_type' => Payment::class,
                'target_id' => $payment->id,
                'before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for Payment', [
                'payment_id' => $payment->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
