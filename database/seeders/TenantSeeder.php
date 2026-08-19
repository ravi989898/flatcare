<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the tenant database seeders
     */
    public function run(): void
    {
        // Seed roles and permissions for the tenant
        $this->call(TenantRoleSeeder::class);
    }
}
