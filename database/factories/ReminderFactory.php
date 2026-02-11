<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reminder>
 */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'reminder_type' => $this->faker->randomElement(['soft', 'normal', 'final']),
            'sent_to' => $this->faker->safeEmail(),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'sent_at' => now(),
            'sent_by' => User::factory(),
        ];
    }

    public function soft(): static
    {
        return $this->state(fn (array $attributes) => [
            'reminder_type' => 'soft',
        ]);
    }

    public function normal(): static
    {
        return $this->state(fn (array $attributes) => [
            'reminder_type' => 'normal',
        ]);
    }

    public function final(): static
    {
        return $this->state(fn (array $attributes) => [
            'reminder_type' => 'final',
        ]);
    }
}
