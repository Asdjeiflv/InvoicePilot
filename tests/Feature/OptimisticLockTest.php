<?php

namespace Tests\Feature;

use App\Exceptions\StaleObjectException;
use App\Models\Invoice;
use App\Models\User;
use App\Traits\HasOptimisticLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OptimisticLockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_detects_stale_object_with_version_mismatch(): void
    {
        $invoice = Invoice::factory()->create([
            'total' => 10000,
            'version' => 1,
        ]);

        $this->expectException(StaleObjectException::class);
        
        // Simulate version mismatch
        $invoice->checkVersion(0); // Expected version 0, but current is 1
    }

    #[Test]
    public function it_allows_update_with_correct_version(): void
    {
        $invoice = Invoice::factory()->create([
            'total' => 10000,
            'version' => 1,
        ]);

        // No exception should be thrown
        $invoice->checkVersion(1);
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_increments_version_on_save(): void
    {
        // Create a test model class with HasOptimisticLock trait
        $invoice = Invoice::factory()->create([
            'total' => 10000,
            'version' => 1,
        ]);

        $originalVersion = $invoice->version;

        $invoice->total = 15000;
        $invoice->save();

        $invoice->refresh();

        // Version should be incremented from 1 to 2
        $this->assertEquals(2, $invoice->version);
        $this->assertEquals($originalVersion + 1, $invoice->version);
    }

    #[Test]
    public function it_handles_concurrent_updates(): void
    {
        $invoice = Invoice::factory()->create([
            'total' => 10000,
            'version' => 1,
        ]);

        // User 1 reads the invoice
        $invoiceUser1 = Invoice::find($invoice->id);
        $versionUser1 = $invoiceUser1->version; // version = 1

        // User 2 reads and updates the invoice
        $invoiceUser2 = Invoice::find($invoice->id);
        $invoiceUser2->total = 15000;
        $invoiceUser2->save(); // This increments version to 2 in database

        // User 1's instance is now stale (still has version 1)
        // Refresh it to get the current version from database
        $invoiceUser1->refresh(); // Now version = 2

        // User 1 tries to update with stale version (expecting version 1, but DB has version 2)
        $this->expectException(StaleObjectException::class);
        $invoiceUser1->checkVersion($versionUser1); // checkVersion(1) when model has version 2
    }
}
