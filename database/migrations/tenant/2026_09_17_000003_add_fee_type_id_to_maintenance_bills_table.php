<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra Charges (function/event usage, hall booking, renovation fund,
     * transfer fee, ...) are stored as ordinary maintenance_bills rows —
     * this nullable fee_type_id is the only thing that distinguishes one
     * from a regular maintenance bill (null = regular bill). Reusing this
     * table rather than a parallel one means Society\ExtraChargeController
     * gets bill viewing, admin payment recording, printable invoices, and
     * resident online payment (Api\V1\Resident\BillPaymentController /
     * RazorpayService) for free — all of that already operates generically
     * on any maintenance_bills row scoped by flat_id.
     */
    public function up(): void
    {
        Schema::table('maintenance_bills', function (Blueprint $table) {
            $table->foreignId('fee_type_id')->nullable()->after('water_reading_id')->constrained('fee_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_type_id');
        });
    }
};
