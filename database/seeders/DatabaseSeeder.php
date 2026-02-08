<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create accounting user
        User::factory()->accounting()->create([
            'name' => 'Accounting User',
            'email' => 'accounting@example.com',
        ]);

        // Create sales user
        User::factory()->sales()->create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
        ]);
    }
}
