<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The day of the month a maintenance bill is due (e.g. 5 means every
 * month's bill is due on the 5th) — see WaterBillingService::record(),
 * which previously hardcoded this to the 5th for every society. A bill
 * becomes overdue, and starts accruing the late fee, the day after.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->unsignedTinyInteger('due_day')->default(5)->after('daily_late_fee');
        });
    }

    public function down(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropColumn('due_day');
        });
    }
};
