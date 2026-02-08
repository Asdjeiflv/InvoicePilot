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
        // Only create test users in non-production environments
        if (!app()->environment('production')) {
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

        // PRODUCTION NOTE: In production, create your first admin user manually:
        // php artisan tinker
        // User::factory()->admin()->create(['name' => 'Your Name', 'email' => 'your@email.com', 'password' => Hash::make('your-secure-password')]);
    }
}
