<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the recurring water_extra_charges that applied when this
     * reading's bill was generated, so later edits to a charge's amount or
     * date range never retroactively change an already-billed month.
     */
    public function up(): void
    {
        Schema::table('water_readings', function (Blueprint $table) {
            $table->decimal('extra_amount', 10, 2)->default(0)->after('current_reading');
        });
    }

    public function down(): void
    {
        Schema::table('water_readings', function (Blueprint $table) {
            $table->dropColumn('extra_amount');
        });
    }
};
