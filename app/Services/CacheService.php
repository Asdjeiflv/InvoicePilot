<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache duration in seconds
     */
    private const CACHE_TTL = 3600; // 1 hour
    private const LONG_CACHE_TTL = 86400; // 24 hours

    /**
     * Get active clients with caching
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Client>
     */
    public function getActiveClients(): mixed
    {
        return Cache::remember('clients.active', self::CACHE_TTL, function () {
            return Client::select('id', 'code', 'company_name', 'payment_terms_days')
                ->orderBy('code')
                ->get();
        });
    }

    /**
     * Get client by ID with caching
     */
    public function getClient(int $id): ?Client
    {
        return Cache::remember("client.{$id}", self::CACHE_TTL, function () use ($id) {
            return Client::find($id);
        });
    }

    /**
     * Clear client cache
     */
    public function clearClientCache(?int $clientId = null): void
    {
        if ($clientId) {
            Cache::forget("client.{$clientId}");
        }

        Cache::forget('clients.active');
    }

    /**
     * Get invoice status options
     *
     * @return array<string, string>
     */
    public function getInvoiceStatuses(): array
    {
        return Cache::remember('options.invoice_statuses', self::LONG_CACHE_TTL, function () {
            return [
                'draft' => '下書き',
                'issued' => '発行済み',
                'partial_paid' => '一部入金',
                'paid' => '入金済み',
                'overdue' => '期限超過',
                'canceled' => 'キャンセル',
            ];
        });
    }

    /**
     * Get payment method options
     *
     * @return array<string, string>
     */
    public function getPaymentMethods(): array
    {
        return Cache::remember('options.payment_methods', self::LONG_CACHE_TTL, function () {
            return [
                'bank_transfer' => '銀行振込',
                'cash' => '現金',
                'credit_card' => 'クレジットカード',
                'other' => 'その他',
            ];
        });
    }

    /**
     * Get quotation status options
     *
     * @return array<string, string>
     */
    public function getQuotationStatuses(): array
    {
        return Cache::remember('options.quotation_statuses', self::LONG_CACHE_TTL, function () {
            return [
                'draft' => '下書き',
                'sent' => '送信済み',
                'approved' => '承認済み',
                'rejected' => '却下',
                'expired' => '有効期限切れ',
            ];
        });
    }

    /**
     * Warm up cache
     */
    public function warmUp(): void
    {
        $this->getActiveClients();
        $this->getInvoiceStatuses();
        $this->getPaymentMethods();
        $this->getQuotationStatuses();
    }

    /**
     * Clear all application cache
     */
    public function clearAll(): void
    {
        Cache::flush();
    }
}
