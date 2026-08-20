<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a bill back to the water reading it was generated from, so
     * re-recording that month's reading updates the same bill instead of
     * creating a duplicate. Null for bills raised manually (one-off
     * charges, penalties, etc. — see PaymentController::store()).
     */
    public function up(): void
    {
        Schema::table('maintenance_bills', function (Blueprint $table) {
            $table->foreignId('water_reading_id')->nullable()->after('flat_id')
                ->constrained('water_readings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('water_reading_id');
        });
    }
};
