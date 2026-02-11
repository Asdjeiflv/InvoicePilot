<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'method' => $this->faker->randomElement(['bank_transfer', 'cash', 'credit_card', 'check', 'other']),
            'reference_no' => $this->faker->optional()->bothify('REF-####-????'),
            'note' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
