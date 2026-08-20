<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform-wide billing defaults for a society, set by the super admin.
     * The society admin only records each flat's monthly water reading;
     * the maintenance amount is derived from these two rates rather than
     * typed in by hand (see WaterReadingController::store()).
     */
    public function up(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->decimal('fixed_maintenance', 10, 2)->default(0)->after('total_blocks');
            $table->decimal('water_unit_rate', 10, 2)->default(0)->after('fixed_maintenance');
        });
    }

    public function down(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropColumn(['fixed_maintenance', 'water_unit_rate']);
        });
    }
};
