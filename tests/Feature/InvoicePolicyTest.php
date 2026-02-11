<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_create_invoices(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($user->can('create', Invoice::class));
    }

    /** @test */
    public function sales_can_create_invoices(): void
    {
        $user = User::factory()->create(['role' => 'sales']);

        $this->assertTrue($user->can('create', Invoice::class));
    }

    /** @test */
    public function accounting_cannot_create_invoices(): void
    {
        $user = User::factory()->create(['role' => 'accounting']);

        $this->assertFalse($user->can('create', Invoice::class));
    }

    /** @test */
    public function auditor_cannot_create_invoices(): void
    {
        $user = User::factory()->create(['role' => 'auditor']);

        $this->assertFalse($user->can('create', Invoice::class));
    }

    /** @test */
    public function admin_can_update_any_invoice(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create(['status' => 'issued']);

        $this->assertTrue($user->can('update', $invoice));
    }

    /** @test */
    public function sales_can_only_update_draft_invoices(): void
    {
        $user = User::factory()->create(['role' => 'sales']);
        $draftInvoice = Invoice::factory()->create(['status' => 'draft']);
        $issuedInvoice = Invoice::factory()->create(['status' => 'issued']);

        $this->assertTrue($user->can('update', $draftInvoice));
        $this->assertFalse($user->can('update', $issuedInvoice));
    }

    /** @test */
    public function accounting_can_update_issued_invoices(): void
    {
        $user = User::factory()->create(['role' => 'accounting']);
        $draftInvoice = Invoice::factory()->create(['status' => 'draft']);
        $issuedInvoice = Invoice::factory()->create(['status' => 'issued']);

        $this->assertFalse($user->can('update', $draftInvoice));
        $this->assertTrue($user->can('update', $issuedInvoice));
    }

    /** @test */
    public function auditor_cannot_update_any_invoice(): void
    {
        $user = User::factory()->create(['role' => 'auditor']);
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $this->assertFalse($user->can('update', $invoice));
    }

    /** @test */
    public function only_admin_can_delete_draft_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sales = User::factory()->create(['role' => 'sales']);
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $this->assertTrue($admin->can('delete', $invoice));
        $this->assertFalse($sales->can('delete', $invoice));
    }

    /** @test */
    public function admin_cannot_delete_issued_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create(['status' => 'issued']);

        $this->assertFalse($admin->can('delete', $invoice));
    }

    /** @test */
    public function all_roles_can_view_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $accounting = User::factory()->create(['role' => 'accounting']);
        $sales = User::factory()->create(['role' => 'sales']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $invoice = Invoice::factory()->create();

        $this->assertTrue($admin->can('view', $invoice));
        $this->assertTrue($accounting->can('view', $invoice));
        $this->assertTrue($sales->can('view', $invoice));
        $this->assertTrue($auditor->can('view', $invoice));
    }

    /** @test */
    public function only_admin_and_accounting_can_cancel_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $accounting = User::factory()->create(['role' => 'accounting']);
        $sales = User::factory()->create(['role' => 'sales']);
        $invoice = Invoice::factory()->create(['status' => 'issued']);

        $this->assertTrue($admin->can('cancel', $invoice));
        $this->assertTrue($accounting->can('cancel', $invoice));
        $this->assertFalse($sales->can('cancel', $invoice));
    }
}
