<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per flat per month: the meter reading the society admin
     * entered, plus the previous reading it was measured against. Units
     * consumed and the resulting maintenance amount are derived from this
     * (see WaterReading::getUnitsAttribute() and WaterReadingController).
     */
    public function up(): void
    {
        Schema::create('water_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();

            $table->date('reading_month'); // stored as the 1st of the billed month
            $table->decimal('previous_reading', 10, 2);
            $table->decimal('current_reading', 10, 2);

            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['flat_id', 'reading_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_readings');
    }
};
