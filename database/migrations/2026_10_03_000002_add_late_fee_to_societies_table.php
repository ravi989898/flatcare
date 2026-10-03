<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A flat fixed penalty plus a per-day charge, applied once a maintenance
 * bill's due date has passed (see MaintenanceBill::lateFeeAmount()) — e.g.
 * late_fee=100, daily_late_fee=10 means a bill 3 days overdue carries
 * 100 + 10*3 = 130 extra, shown on the invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->decimal('late_fee', 10, 2)->default(0)->after('water_unit_rate');
            $table->decimal('daily_late_fee', 10, 2)->default(0)->after('late_fee');
        });
    }

    public function down(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropColumn(['late_fee', 'daily_late_fee']);
        });
    }
};
