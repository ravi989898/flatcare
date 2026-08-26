<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks every Razorpay order this backend has created, independently
     * of the client — it's what lets pay/verify trust a stored, server-
     * computed amount instead of anything the app claims, and it's the
     * idempotency record that makes replaying a captured payment a no-op.
     * See App\Services\RazorpayService.
     */
    public function up(): void
    {
        Schema::create('razorpay_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('maintenance_bills')->cascadeOnDelete();

            $table->string('razorpay_order_id')->unique();

            // Snapshot of the bill's balance at the moment the order was
            // created — this, not anything the client sends back at verify
            // time, is the only amount ever trusted.
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('INR');

            $table->enum('status', ['created', 'processing', 'paid', 'failed'])->default('created');

            // Populated only once a payment is actually verified. The
            // unique constraint on razorpay_payment_id is a DB-level
            // replay guard that holds even if the app-level lock/status
            // check above it were ever buggy.
            $table->string('razorpay_payment_id')->nullable()->unique();
            $table->string('razorpay_signature')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('failure_reason')->nullable();

            // Raw payment.fetch() response from Razorpay, kept for dispute
            // resolution — never shown to the resident.
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('bill_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('razorpay_orders');
    }
};
