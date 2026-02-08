<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
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

        return [
            'quotation_no' => 'Q-' . now()->year . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the quotation has a specific quotation number.
     */
    public function withQuotationNo(string $quotationNo): static
    {
        return $this->state(fn (array $attributes) => [
            'quotation_no' => $quotationNo,
        ]);
    }

    /**
     * Indicate that the quotation is sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    /**
     * Indicate that the quotation is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
