<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A charge is paused rather than deleted — WaterBillingService only
     * folds active() charges into a bill, but keeping an inactive row (and
     * its history) means re-activating it later doesn't lose its original
     * start_date/remarks.
     */
    public function up(): void
    {
        Schema::table('water_extra_charges', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('water_extra_charges', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
