<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Society-managed catalog of one-off charge types (function/event usage,
     * common hall booking, renovation fund, flat transfer fee, ...) that
     * Society\ExtraChargeController lets Society Admin raise against a flat
     * — same shape as service_providers (a short admin-managed named list),
     * plus a default_amount so raising a charge can pre-fill the amount.
     */
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->decimal('default_amount', 10, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
