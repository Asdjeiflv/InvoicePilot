<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class ClientObserver
{
    /**
     * 監査ログに記録する属性
     */
    private const AUDITABLE_ATTRIBUTES = [
        'code',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'payment_terms_days',
        'closing_day',
    ];

    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        $this->logAudit('created', $client, null, $client->only(self::AUDITABLE_ATTRIBUTES));
    }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        // Get original values before update
        $before = $client->getOriginal();
        $after = $client->getAttributes();

        // Only log changed auditable attributes
        $changedBefore = array_intersect_key($before, array_flip(self::AUDITABLE_ATTRIBUTES));
        $changedAfter = array_intersect_key($after, array_flip(self::AUDITABLE_ATTRIBUTES));

        // Filter to only attributes that actually changed
        $changes = array_filter($changedAfter, function ($value, $key) use ($changedBefore) {
            return !isset($changedBefore[$key]) || $changedBefore[$key] !== $value;
        }, ARRAY_FILTER_USE_BOTH);

        if (!empty($changes)) {
            $beforeFiltered = array_intersect_key($changedBefore, $changes);
            $this->logAudit('updated', $client, $beforeFiltered, $changes);
        }
    }

    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        $action = $client->isForceDeleting() ? 'force_deleted' : 'deleted';
        $this->logAudit($action, $client, $client->only(self::AUDITABLE_ATTRIBUTES), null);
    }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        $this->logAudit('restored', $client, null, $client->only(self::AUDITABLE_ATTRIBUTES));
    }

    /**
     * 監査ログを記録（エラーハンドリング付き）
     */
    private function logAudit(string $action, Client $client, ?array $before, ?array $after): void
    {
        try {
            AuditLog::log(
                $action,
                Client::class,
                $client->id,
                $before,
                $after
            );
        } catch (\Exception $e) {
            // 監査ログの失敗でメイン操作を中断しない
            Log::error('監査ログの記録に失敗しました', [
                'action' => $action,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
