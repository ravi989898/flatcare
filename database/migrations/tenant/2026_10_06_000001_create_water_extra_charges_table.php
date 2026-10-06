<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recurring charges (festival fund, lift AMC installment, ...) that
     * apply automatically to every water-reading bill generated across all
     * flats while the billing month falls within [start_date, end_date]
     * (end_date null = ongoing, no end). See WaterBillingService, which
     * sums the matching rows into each month's bill amount. flat_id is kept
     * (nullable, null = every flat) so a future admin UI can scope a charge
     * to one flat without a schema change — today's "Add Extra Charges" form
     * only collects amount/start_date/end_date and always leaves it null.
     */
    public function up(): void
    {
        Schema::create('water_extra_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 150)->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_extra_charges');
    }
};
