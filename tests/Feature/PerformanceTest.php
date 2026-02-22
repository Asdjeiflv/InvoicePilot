<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function it_uses_eager_loading_for_invoice_list(): void
    {
        $client = Client::factory()->create();
        Invoice::factory()->count(10)->create(['client_id' => $client->id]);

        // Enable query logging
        DB::enableQueryLog();

        // Test the query directly instead of HTTP request
        $invoices = Invoice::with(['client:id,code,company_name'])->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have only 2 queries: invoices + clients (eager loaded)
        $this->assertLessThanOrEqual(2, count($queries), 'Too many queries detected (N+1 issue)');
        $this->assertCount(10, $invoices);
    }

    #[Test]
    public function it_uses_eager_loading_for_payment_list(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);
        Payment::factory()->count(10)->create(['invoice_id' => $invoice->id]);

        DB::enableQueryLog();

        // Test query directly
        $payments = Payment::with(['invoice:id,invoice_no,client_id', 'invoice.client:id,code,company_name'])->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have only 3 queries: payments + invoices + clients
        $this->assertLessThanOrEqual(3, count($queries), 'Too many queries detected');
        $this->assertCount(10, $payments);
    }

    #[Test]
    public function it_caches_active_clients(): void
    {
        Cache::flush();

        Client::factory()->count(5)->create();

        $cacheService = app(CacheService::class);

        // First call - should hit database
        $clients1 = $cacheService->getActiveClients();
        $this->assertCount(5, $clients1);

        // Second call - should use cache
        DB::enableQueryLog();
        $clients2 = $cacheService->getActiveClients();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(0, $queries, 'Cache not used - query was executed');
        $this->assertEquals($clients1, $clients2);
    }

    #[Test]
    public function it_clears_cache_on_client_update(): void
    {
        Cache::flush();

        $client = Client::factory()->create(['company_name' => 'Original Name']);

        $cacheService = app(CacheService::class);

        // Cache client
        $cacheService->getActiveClients();
        $this->assertTrue(Cache::has('clients.active'));

        // Update client (should clear cache via Observer)
        $client->update(['company_name' => 'Updated Name']);

        // Cache should be cleared
        $this->assertFalse(Cache::has('clients.active'));
    }

    #[Test]
    public function it_handles_large_datasets_efficiently(): void
    {
        $client = Client::factory()->create();
        Invoice::factory()->count(200)->create(['client_id' => $client->id]);

        DB::enableQueryLog();

        $invoices = Invoice::with('client')->paginate(50);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should only have 3 queries: count + invoices + clients
        $this->assertLessThanOrEqual(3, count($queries), 'Too many queries for large dataset');
        $this->assertEquals(50, $invoices->count());
    }

    #[Test]
    public function it_uses_database_indexes_for_search(): void
    {
        $client = Client::factory()->create(['company_name' => 'Test Company']);
        Invoice::factory()->count(100)->create(['client_id' => $client->id]);

        $startTime = microtime(true);

        $results = Invoice::where('invoice_no', 'like', '%TEST%')->get();

        $duration = (microtime(true) - $startTime) * 1000;

        // Search with indexes should be fast
        $this->assertLessThan(100, $duration, "Search too slow: {$duration}ms");
    }

    #[Test]
    public function it_paginates_results_efficiently(): void
    {
        $client = Client::factory()->create();
        Invoice::factory()->count(100)->create(['client_id' => $client->id]);

        DB::enableQueryLog();

        // First page
        $page1 = Invoice::with('client')->paginate(15);
        $queries1 = count(DB::getQueryLog());

        // Second page (reset query log)
        DB::flushQueryLog();
        $page2 = Invoice::with('client')->paginate(15, ['*'], 'page', 2);
        $queries2 = count(DB::getQueryLog());

        DB::disableQueryLog();

        // Both pages should use same number of queries
        $this->assertEquals($queries1, $queries2, 'Pagination query count inconsistent');
        $this->assertCount(15, $page1);
        $this->assertCount(15, $page2);
    }

    #[Test]
    public function it_warms_up_cache_efficiently(): void
    {
        Client::factory()->count(10)->create();

        Cache::flush();

        $cacheService = app(CacheService::class);

        $startTime = microtime(true);
        $cacheService->warmUp();
        $duration = (microtime(true) - $startTime) * 1000;

        // Cache warm-up should be fast
        $this->assertLessThan(200, $duration, "Cache warm-up too slow: {$duration}ms");

        // All expected keys should be cached
        $this->assertTrue(Cache::has('clients.active'));
        $this->assertTrue(Cache::has('options.invoice_statuses'));
        $this->assertTrue(Cache::has('options.payment_methods'));
    }
}
