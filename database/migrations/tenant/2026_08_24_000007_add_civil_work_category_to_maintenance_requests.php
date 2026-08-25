<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The mockup's "New Service Request" category grid includes Civil Work,
     * which had no matching value in the original category enum
     * (plumbing/electrical/carpentry/painting/cleaning/security/lift/
     * common_area/other) — see MaintenanceRequest::CATEGORIES.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE maintenance_requests MODIFY category ENUM('plumbing', 'electrical', 'carpentry', 'painting', 'cleaning', 'security', 'lift', 'common_area', 'civil_work', 'other') NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::statement("UPDATE maintenance_requests SET category = 'other' WHERE category = 'civil_work'");
        DB::statement("ALTER TABLE maintenance_requests MODIFY category ENUM('plumbing', 'electrical', 'carpentry', 'painting', 'cleaning', 'security', 'lift', 'common_area', 'other') NOT NULL DEFAULT 'other'");
    }
};
