<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds 'online' (a Razorpay-collected payment) to payments.payment_method
     * alongside the existing manually-recorded methods. This project doesn't
     * have doctrine/dbal installed, so Schema::table()->enum()->change() isn't
     * available — a raw ALTER TABLE is the standard workaround for widening a
     * MySQL enum without it.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE payments MODIFY payment_method ENUM('cash', 'bank_transfer', 'upi', 'cheque', 'other', 'online') NOT NULL DEFAULT 'cash'"
        );
    }

    /**
     * Only safe if no 'online' rows exist yet — reversing past that point
     * would need those rows remapped or deleted first.
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE payments MODIFY payment_method ENUM('cash', 'bank_transfer', 'upi', 'cheque', 'other') NOT NULL DEFAULT 'cash'"
        );
    }
};
