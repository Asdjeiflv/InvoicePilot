<?php

namespace Tests\Feature;

use App\Exceptions\StaleObjectException;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $sales;
    private User $accounting;
    private User $auditor;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->sales = User::factory()->create(['role' => 'sales']);
        $this->accounting = User::factory()->create(['role' => 'accounting']);
        $this->auditor = User::factory()->create(['role' => 'auditor']);

        $this->client = Client::factory()->create();
    }

    /** @test */
    public function admin_can_create_invoice(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('invoices.store'), $this->validInvoiceData());

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function sales_can_create_invoice(): void
    {
        $response = $this->actingAs($this->sales)
            ->post(route('invoices.store'), $this->validInvoiceData());

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
        ]);
    }

    /** @test */
    public function accounting_cannot_create_invoice(): void
    {
        $response = $this->actingAs($this->accounting)
            ->post(route('invoices.store'), $this->validInvoiceData());

        $response->assertForbidden();
    }

    /** @test */
    public function auditor_cannot_create_invoice(): void
    {
        $response = $this->actingAs($this->auditor)
            ->post(route('invoices.store'), $this->validInvoiceData());

        $response->assertForbidden();
    }

    /** @test */
    public function it_generates_unique_invoice_number_on_create(): void
    {
        $this->actingAs($this->admin);

        $response1 = $this->post(route('invoices.store'), $this->validInvoiceData());
        $response2 = $this->post(route('invoices.store'), $this->validInvoiceData());

        $invoice1 = Invoice::latest()->skip(1)->first();
        $invoice2 = Invoice::latest()->first();

        $this->assertNotEquals($invoice1->invoice_no, $invoice2->invoice_no);
    }

    /** @test */
    public function it_calculates_totals_correctly_on_create(): void
    {
        $this->actingAs($this->admin);

        $data = $this->validInvoiceData([
            'items' => [
                [
                    'description' => 'Item 1',
                    'quantity' => 2,
                    'unit_price' => 1000,
                    'tax_rate' => 10,
                ],
                [
                    'description' => 'Item 2',
                    'quantity' => 3,
                    'unit_price' => 500,
                    'tax_rate' => 10,
                ],
            ],
        ]);

        $this->post(route('invoices.store'), $data);

        $invoice = Invoice::latest()->first();

        // Subtotal: (2 * 1000) + (3 * 500) = 3500
        $this->assertEquals(3500, $invoice->subtotal);
        
        // Tax: 3500 * 0.1 = 350
        $this->assertEquals(350, $invoice->tax_total);
        
        // Total: 3500 + 350 = 3850
        $this->assertEquals(3850, $invoice->total);
        $this->assertEquals(3850, $invoice->balance_due);
    }

    /** @test */
    public function admin_can_update_any_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $this->validInvoiceData());

        $response->assertRedirect();
    }

    /** @test */
    public function sales_can_only_update_draft_invoices(): void
    {
        $draftInvoice = Invoice::factory()->create(['status' => 'draft']);
        $issuedInvoice = Invoice::factory()->create(['status' => 'issued']);

        // Can update draft
        $response1 = $this->actingAs($this->sales)
            ->put(route('invoices.update', $draftInvoice), $this->validInvoiceData());
        $response1->assertRedirect();

        // Cannot update issued
        $response2 = $this->actingAs($this->sales)
            ->put(route('invoices.update', $issuedInvoice), $this->validInvoiceData());
        $response2->assertForbidden();
    }

    /** @test */
    public function accounting_can_update_issued_invoices(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'issued']);

        $response = $this->actingAs($this->accounting)
            ->put(route('invoices.update', $invoice), $this->validInvoiceData());

        $response->assertRedirect();
    }

    /** @test */
    public function auditor_cannot_update_any_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($this->auditor)
            ->put(route('invoices.update', $invoice), $this->validInvoiceData());

        $response->assertForbidden();
    }

    /** @test */
    public function it_prevents_editing_paid_invoices(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'paid']);

        $response = $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $this->validInvoiceData());

        $response->assertRedirect();
        $response->assertSessionHasErrors('client_id');
    }

    /** @test */
    public function it_prevents_editing_canceled_invoices(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'canceled']);

        $response = $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $this->validInvoiceData());

        $response->assertRedirect();
        $response->assertSessionHasErrors('client_id');
    }

    /** @test */
    public function it_recalculates_totals_on_update(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'draft',
            'total' => 1000,
        ]);

        $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $this->validInvoiceData([
                'items' => [
                    [
                        'description' => 'New Item',
                        'quantity' => 5,
                        'unit_price' => 1000,
                        'tax_rate' => 10,
                    ],
                ],
            ]));

        $invoice->refresh();

        // Subtotal: 5 * 1000 = 5000
        $this->assertEquals(5000, $invoice->subtotal);
        
        // Tax: 5000 * 0.1 = 500
        $this->assertEquals(500, $invoice->tax_total);
        
        // Total: 5500
        $this->assertEquals(5500, $invoice->total);
    }

    /** @test */
    public function it_increments_version_on_update(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'draft',
            'version' => 1,
        ]);

        $originalVersion = $invoice->version;

        $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $this->validInvoiceData());

        $invoice->refresh();

        $this->assertEquals($originalVersion + 1, $invoice->version);
        $this->assertEquals(2, $invoice->version);
    }

    /** @test */
    public function it_detects_concurrent_updates_with_stale_version(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'draft',
            'version' => 1,
        ]);

        // Simulate version mismatch
        $data = $this->validInvoiceData(['version' => 0]);

        $response = $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('別のユーザーによって', session('error'));
    }

    /** @test */
    public function it_allows_update_with_correct_version(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'draft',
            'version' => 1,
        ]);

        $data = $this->validInvoiceData(['version' => 1]);

        $response = $this->actingAs($this->admin)
            ->put(route('invoices.update', $invoice), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admin_can_delete_draft_invoices_without_payments(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->admin)
            ->delete(route('invoices.destroy', $invoice));

        $response->assertRedirect();
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    /** @test */
    public function it_prevents_deletion_of_invoices_with_payments(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);
        Payment::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('invoices.destroy', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    /** @test */
    public function non_admin_cannot_delete_invoices(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->sales)
            ->delete(route('invoices.destroy', $invoice));

        $response->assertForbidden();
    }

    private function validInvoiceData(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'tax_rate' => 10,
                ],
            ],
            'notes' => 'Test notes',
        ], $overrides);
    }
}
