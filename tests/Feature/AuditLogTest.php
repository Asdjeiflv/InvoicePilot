<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function it_logs_invoice_creation(): void
    {
        $this->actingAs($this->user);

        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'invoice_no' => 'I-2026-00001',
            'total' => 10000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'created',
            'target_type' => Invoice::class,
            'target_id' => $invoice->id,
        ]);

        $log = AuditLog::where('target_type', Invoice::class)
            ->where('target_id', $invoice->id)
            ->latest()
            ->first();

        $this->assertNull($log->before_json);
        $this->assertNotNull($log->after_json);

        $afterData = json_decode($log->after_json, true);
        $this->assertEquals('I-2026-00001', $afterData['invoice_no']);
        $this->assertEquals(10000, $afterData['total']);
    }

    #[Test]
    public function it_logs_invoice_update_with_changes(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'total' => 10000,
            'status' => 'draft',
        ]);

        AuditLog::query()->delete(); // Clear creation logs

        $invoice->update([
            'total' => 15000,
            'status' => 'issued',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'updated',
            'target_type' => Invoice::class,
            'target_id' => $invoice->id,
        ]);

        $log = AuditLog::where('target_type', Invoice::class)
            ->where('target_id', $invoice->id)
            ->where('action', 'updated')
            ->latest()
            ->first();

        $beforeData = json_decode($log->before_json, true);
        $afterData = json_decode($log->after_json, true);

        $this->assertEquals(10000, $beforeData['total']);
        $this->assertEquals('draft', $beforeData['status']);

        $this->assertEquals(15000, $afterData['total']);
        $this->assertEquals('issued', $afterData['status']);
    }

    #[Test]
    public function it_logs_invoice_deletion(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::factory()->create(['total' => 10000]);

        AuditLog::query()->delete(); // Clear creation logs

        $invoice->delete();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'deleted',
            'target_type' => Invoice::class,
            'target_id' => $invoice->id,
        ]);

        $log = AuditLog::where('target_type', Invoice::class)
            ->where('target_id', $invoice->id)
            ->where('action', 'deleted')
            ->latest()
            ->first();

        $this->assertNotNull($log->before_json);
        $this->assertNull($log->after_json);
    }

    #[Test]
    public function it_logs_payment_creation(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::factory()->create(['total' => 10000]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'created',
            'target_type' => Payment::class,
            'target_id' => $payment->id,
        ]);

        $log = AuditLog::where('target_type', Payment::class)
            ->where('target_id', $payment->id)
            ->latest()
            ->first();

        $afterData = json_decode($log->after_json, true);
        $this->assertEquals(5000, $afterData['amount']);
    }

    #[Test]
    public function it_logs_payment_update(): void
    {
        $this->actingAs($this->user);

        $payment = Payment::factory()->create(['amount' => 5000]);

        AuditLog::query()->delete(); // Clear creation logs

        $payment->update(['amount' => 8000]);

        $log = AuditLog::where('target_type', Payment::class)
            ->where('target_id', $payment->id)
            ->where('action', 'updated')
            ->latest()
            ->first();

        $beforeData = json_decode($log->before_json, true);
        $afterData = json_decode($log->after_json, true);

        $this->assertEquals(5000, $beforeData['amount']);
        $this->assertEquals(8000, $afterData['amount']);
    }

    #[Test]
    public function it_logs_quotation_creation(): void
    {
        $this->actingAs($this->user);

        $client = Client::factory()->create();
        $quotation = Quotation::factory()->create([
            'client_id' => $client->id,
            'quotation_no' => 'Q-2026-00001',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'created',
            'target_type' => Quotation::class,
            'target_id' => $quotation->id,
        ]);
    }

    #[Test]
    public function it_logs_quotation_status_change(): void
    {
        $this->actingAs($this->user);

        $quotation = Quotation::factory()->create(['status' => 'draft']);

        AuditLog::query()->delete(); // Clear creation logs

        $quotation->update(['status' => 'approved']);

        $log = AuditLog::where('target_type', Quotation::class)
            ->where('target_id', $quotation->id)
            ->where('action', 'updated')
            ->latest()
            ->first();

        $beforeData = json_decode($log->before_json, true);
        $afterData = json_decode($log->after_json, true);

        $this->assertEquals('draft', $beforeData['status']);
        $this->assertEquals('approved', $afterData['status']);
    }

    #[Test]
    public function it_records_ip_address_in_audit_log(): void
    {
        $invoice = Invoice::factory()->create();

        $log = AuditLog::where('target_type', Invoice::class)
            ->where('target_id', $invoice->id)
            ->latest()
            ->first();

        $this->assertNotNull($log->ip_address);
    }

    #[Test]
    public function it_does_not_log_when_only_updated_at_changes(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::factory()->create();

        AuditLog::query()->delete(); // Clear creation logs

        // Touch only updates updated_at
        $invoice->touch();

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'updated',
            'target_type' => Invoice::class,
            'target_id' => $invoice->id,
        ]);
    }
}
