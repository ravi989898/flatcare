<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments module — tenant database. Manual recording only for now:
     * the admin raises a maintenance bill per flat and logs payments
     * against it as they're received (cash, bank transfer, UPI, cheque).
     * Real online payment collection (Razorpay per the architecture doc)
     * is a separate, larger integration on top of this ledger.
     */
    public function up(): void
    {
        Schema::create('maintenance_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();

            $table->string('title'); // e.g. "August 2026 Maintenance"
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->text('notes')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('due_date');
            $table->index('flat_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('maintenance_bills')->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'upi', 'cheque', 'other'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('bill_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('maintenance_bills');
    }
};
