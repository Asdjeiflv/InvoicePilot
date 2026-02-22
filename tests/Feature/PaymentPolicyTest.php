<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function only_admin_and_accounting_can_create_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $accounting = User::factory()->create(['role' => 'accounting']);
        $sales = User::factory()->create(['role' => 'sales']);
        $auditor = User::factory()->create(['role' => 'auditor']);

        $this->assertTrue($admin->can('create', Payment::class));
        $this->assertTrue($accounting->can('create', Payment::class));
        $this->assertFalse($sales->can('create', Payment::class));
        $this->assertFalse($auditor->can('create', Payment::class));
    }

    #[Test]
    public function only_admin_and_accounting_can_update_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $accounting = User::factory()->create(['role' => 'accounting']);
        $sales = User::factory()->create(['role' => 'sales']);
        $payment = Payment::factory()->create();

        $this->assertTrue($admin->can('update', $payment));
        $this->assertTrue($accounting->can('update', $payment));
        $this->assertFalse($sales->can('update', $payment));
    }

    #[Test]
    public function only_admin_can_delete_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $accounting = User::factory()->create(['role' => 'accounting']);
        $payment = Payment::factory()->create();

        $this->assertTrue($admin->can('delete', $payment));
        $this->assertFalse($accounting->can('delete', $payment));
    }
}
