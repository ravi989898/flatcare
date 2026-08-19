<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Calls environment-specific seeders for main and tenant databases.
     */
    public function run(): void
    {
        // Only run main app seeder on main database
        if (config('database.default') === 'main' || app()->environment('production')) {
            $this->call(MainAppSeeder::class);
        }
    }
}
