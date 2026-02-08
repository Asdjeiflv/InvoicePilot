<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 1000000);
        $taxRate = 0.10; // 10% tax rate
        $taxTotal = $subtotal * $taxRate;
        $total = $subtotal + $taxTotal;
        $paidAmount = fake()->optional(0.3)->randomFloat(2, 0, $total) ?? 0;
        $balanceDue = $total - $paidAmount;

        return [
            'invoice_no' => 'I-' . now()->year . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'quotation_id' => null,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'balance_due' => $balanceDue,
            'status' => 'draft',
            'sent_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the invoice has a specific invoice number.
     */
    public function withInvoiceNo(string $invoiceNo): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_no' => $invoiceNo,
        ]);
    }

    /**
     * Indicate that the invoice is issued.
     */
    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'paid_amount' => $attributes['total'],
                'balance_due' => 0,
            ];
        });
    }

    /**
     * Indicate that the invoice is from a quotation.
     */
    public function fromQuotation(?Quotation $quotation = null): static
    {
        return $this->state(fn (array $attributes) => [
            'quotation_id' => $quotation?->id ?? Quotation::factory(),
        ]);
    }
}
