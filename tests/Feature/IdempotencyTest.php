<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\IdempotencyKey;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'sales']);
    }

    #[Test]
    public function it_prevents_duplicate_invoice_creation_with_same_idempotency_key(): void
    {
        $client = Client::factory()->create();

        $invoiceData = [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax_rate' => 10,
                ],
            ],
        ];

        $idempotencyKey = 'test-key-' . now()->timestamp;

        // First request
        $response1 = $this->actingAs($this->user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('invoices.store'), $invoiceData);

        $response1->assertRedirect();

        // Second request with same key
        $response2 = $this->actingAs($this->user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('invoices.store'), $invoiceData);

        // Should return cached response
        $this->assertEquals($response1->getStatusCode(), $response2->getStatusCode());
        $this->assertTrue($response2->headers->has('X-Idempotency-Replay'));

        // Only one invoice should be created
        $this->assertEquals(1, Invoice::count());
    }

    #[Test]
    public function it_allows_different_requests_with_different_keys(): void
    {
        $client = Client::factory()->create();

        $invoiceData = [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax_rate' => 10,
                ],
            ],
        ];

        // First request with key1
        $response1 = $this->actingAs($this->user)
            ->withHeader('Idempotency-Key', 'key-1')
            ->post(route('invoices.store'), $invoiceData);

        $response1->assertRedirect();

        // Second request with key2
        $response2 = $this->actingAs($this->user)
            ->withHeader('Idempotency-Key', 'key-2')
            ->post(route('invoices.store'), $invoiceData);

        $response2->assertRedirect();

        // Two invoices should be created
        $this->assertEquals(2, Invoice::count());
    }

    #[Test]
    public function it_works_without_idempotency_key(): void
    {
        $client = Client::factory()->create();

        $invoiceData = [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax_rate' => 10,
                ],
            ],
        ];

        // Request without idempotency key
        $response = $this->actingAs($this->user)
            ->post(route('invoices.store'), $invoiceData);

        $response->assertRedirect();
        $this->assertEquals(1, Invoice::count());
    }

    #[Test]
    public function it_expires_idempotency_keys_after_24_hours(): void
    {
        $key = IdempotencyKey::create([
            'key' => 'old-key',
            'user_id' => $this->user->id,
            'response_json' => '{"status":"success"}',
            'response_status' => 200,
        ]);

        // Manually update created_at to 25 hours ago
        $key->created_at = now()->subHours(25);
        $key->save();

        IdempotencyKey::cleanup();

        $this->assertDatabaseMissing('idempotency_keys', [
            'id' => $key->id,
        ]);
    }

    #[Test]
    public function it_isolates_keys_per_user(): void
    {
        $user2 = User::factory()->create(['role' => 'sales']);
        $client = Client::factory()->create();

        $invoiceData = [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax_rate' => 10,
                ],
            ],
        ];

        $idempotencyKey = 'shared-key';

        // User 1 creates invoice
        $response1 = $this->actingAs($this->user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('invoices.store'), $invoiceData);

        $response1->assertRedirect();

        // User 2 creates invoice with same key
        $response2 = $this->actingAs($user2)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('invoices.store'), $invoiceData);

        $response2->assertRedirect();

        // Two invoices should be created (one per user)
        $this->assertEquals(2, Invoice::count());
    }

    #[Test]
    public function it_does_not_set_json_content_type_for_redirect_responses(): void
    {
        $client = Client::factory()->create();

        $invoiceData = [
            'client_id' => $client->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax_rate' => 10,
                ],
            ],
        ];

        $idempotencyKey = 'redirect-test-key';

        // First request - creates invoice and redirects
        $response1 = $this->actingAs($this->user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('invoices.store'), $invoiceData);

        $response1->assertRedirect();

        // Second request - should replay redirect without JSON content type
        $response2 = $this->actingAs($this->user)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('invoices.store'), $invoiceData);

        $this->assertTrue($response2->headers->has('X-Idempotency-Replay'));
        $this->assertFalse(
            str_contains($response2->headers->get('Content-Type', ''), 'application/json'),
            'Redirect response should not have JSON content type'
        );
    }
}
