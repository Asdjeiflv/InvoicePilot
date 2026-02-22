<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $accounting;
    private User $sales;
    private User $auditor;
    private Client $client;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->accounting = User::factory()->create(['role' => 'accounting']);
        $this->sales = User::factory()->create(['role' => 'sales']);
        $this->auditor = User::factory()->create(['role' => 'auditor']);

        $this->client = Client::factory()->create();
        
        $this->invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => 'issued',
            'total' => 10000,
            'paid_amount' => 0,
            'balance_due' => 10000,
        ]);
    }

    #[Test]
    public function admin_can_create_payment(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('payments.store'), $this->validPaymentData());

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 5000,
        ]);
    }

    #[Test]
    public function accounting_can_create_payment(): void
    {
        $response = $this->actingAs($this->accounting)
            ->post(route('payments.store'), $this->validPaymentData());

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
        ]);
    }

    #[Test]
    public function sales_cannot_create_payment(): void
    {
        $response = $this->actingAs($this->sales)
            ->post(route('payments.store'), $this->validPaymentData());

        $response->assertForbidden();
    }

    #[Test]
    public function auditor_cannot_create_payment(): void
    {
        $response = $this->actingAs($this->auditor)
            ->post(route('payments.store'), $this->validPaymentData());

        $response->assertForbidden();
    }

    #[Test]
    public function it_recalculates_invoice_balance_after_payment(): void
    {
        $this->actingAs($this->admin)
            ->post(route('payments.store'), $this->validPaymentData(['amount' => 3000]));

        $this->invoice->refresh();

        $this->assertEquals(3000, $this->invoice->paid_amount);
        $this->assertEquals(7000, $this->invoice->balance_due);
        $this->assertEquals('partial_paid', $this->invoice->status);
    }

    #[Test]
    public function it_marks_invoice_as_paid_when_fully_paid(): void
    {
        $this->actingAs($this->admin)
            ->post(route('payments.store'), $this->validPaymentData(['amount' => 10000]));

        $this->invoice->refresh();

        $this->assertEquals(10000, $this->invoice->paid_amount);
        $this->assertEquals(0, $this->invoice->balance_due);
        $this->assertEquals('paid', $this->invoice->status);
    }

    #[Test]
    public function it_handles_multiple_partial_payments(): void
    {
        $this->actingAs($this->admin);

        // First payment
        $this->post(route('payments.store'), $this->validPaymentData(['amount' => 3000]));
        $this->invoice->refresh();
        $this->assertEquals(3000, $this->invoice->paid_amount);
        $this->assertEquals('partial_paid', $this->invoice->status);

        // Second payment
        $this->post(route('payments.store'), $this->validPaymentData(['amount' => 4000]));
        $this->invoice->refresh();
        $this->assertEquals(7000, $this->invoice->paid_amount);
        $this->assertEquals('partial_paid', $this->invoice->status);

        // Final payment
        $this->post(route('payments.store'), $this->validPaymentData(['amount' => 3000]));
        $this->invoice->refresh();
        $this->assertEquals(10000, $this->invoice->paid_amount);
        $this->assertEquals('paid', $this->invoice->status);
    }

    #[Test]
    public function it_prevents_overpayment(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('payments.store'), $this->validPaymentData(['amount' => 15000]));

        $response->assertSessionHasErrors('amount');
        $this->assertStringContainsString('残高', session('errors')->first('amount'));
    }

    #[Test]
    public function it_prevents_payment_on_draft_invoice(): void
    {
        $draftInvoice = Invoice::factory()->create([
            'status' => 'draft',
            'total' => 5000,
            'balance_due' => 5000,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('payments.store'), $this->validPaymentData([
                'invoice_id' => $draftInvoice->id,
            ]));

        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function it_prevents_payment_on_canceled_invoice(): void
    {
        $canceledInvoice = Invoice::factory()->create([
            'status' => 'canceled',
            'total' => 5000,
            'balance_due' => 5000,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('payments.store'), $this->validPaymentData([
                'invoice_id' => $canceledInvoice->id,
            ]));

        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function admin_can_update_payment(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 5000,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('payments.update', $payment), $this->validPaymentData([
                'amount' => 3000,
            ]));

        $response->assertRedirect();
        
        $payment->refresh();
        $this->assertEquals(3000, $payment->amount);
    }

    #[Test]
    public function it_recalculates_balance_after_payment_update(): void
    {
        // Initial payment of 3000
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 3000,
        ]);

        // Recalculate to set initial state
        $this->invoice->paid_amount = 3000;
        $this->invoice->balance_due = 7000;
        $this->invoice->status = 'partial_paid';
        $this->invoice->save();

        // Update payment to 5000
        $this->actingAs($this->admin)
            ->put(route('payments.update', $payment), $this->validPaymentData([
                'amount' => 5000,
            ]));

        $this->invoice->refresh();

        $this->assertEquals(5000, $this->invoice->paid_amount);
        $this->assertEquals(5000, $this->invoice->balance_due);
    }

    #[Test]
    public function it_increments_version_on_payment_update(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'version' => 1,
        ]);

        $originalVersion = $payment->version;

        $this->actingAs($this->admin)
            ->put(route('payments.update', $payment), $this->validPaymentData());

        $payment->refresh();

        $this->assertEquals($originalVersion + 1, $payment->version);
        $this->assertEquals(2, $payment->version);
    }

    #[Test]
    public function it_detects_concurrent_payment_updates(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'version' => 1,
        ]);

        $data = $this->validPaymentData(['version' => 0]);

        $response = $this->actingAs($this->admin)
            ->put(route('payments.update', $payment), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('別のユーザーによって', session('error'));
    }

    #[Test]
    public function admin_can_delete_payment(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 5000,
        ]);

        // Set invoice state
        $this->invoice->paid_amount = 5000;
        $this->invoice->balance_due = 5000;
        $this->invoice->save();

        $response = $this->actingAs($this->admin)
            ->delete(route('payments.destroy', $payment));

        $response->assertRedirect();
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    #[Test]
    public function it_recalculates_balance_after_payment_deletion(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 5000,
        ]);

        // Set invoice state
        $this->invoice->paid_amount = 5000;
        $this->invoice->balance_due = 5000;
        $this->invoice->status = 'partial_paid';
        $this->invoice->save();

        $this->actingAs($this->admin)
            ->delete(route('payments.destroy', $payment));

        $this->invoice->refresh();

        $this->assertEquals(0, $this->invoice->paid_amount);
        $this->assertEquals(10000, $this->invoice->balance_due);
        $this->assertEquals('issued', $this->invoice->status);
    }

    #[Test]
    public function non_admin_and_non_accounting_cannot_delete_payment(): void
    {
        $payment = Payment::factory()->create(['invoice_id' => $this->invoice->id]);

        $response = $this->actingAs($this->sales)
            ->delete(route('payments.destroy', $payment));

        $response->assertForbidden();
    }

    private function validPaymentData(array $overrides = []): array
    {
        return array_merge([
            'invoice_id' => $this->invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 5000,
            'method' => 'bank_transfer',
            'reference_no' => 'REF-' . uniqid(),
            'note' => 'Test payment',
        ], $overrides);
    }
}
