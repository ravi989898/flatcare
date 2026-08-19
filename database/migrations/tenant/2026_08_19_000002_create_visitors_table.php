<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visitor management — tenant database. Modeled as a gate register:
     * the admin/security desk logs an entry (check-in) and later records
     * the exit (check-out) on the same row, rather than a separate
     * pre-approval workflow — there's no resident self-service portal yet
     * to drive pre-approvals from.
     */
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();

            $table->string('visitor_name');
            $table->string('visitor_phone')->nullable();
            $table->enum('purpose', ['guest', 'delivery', 'cab', 'service', 'other'])->default('guest');
            $table->string('vehicle_number')->nullable();

            $table->enum('status', ['checked_in', 'checked_out', 'denied'])->default('checked_in');

            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();

            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('flat_id');
            $table->index('check_in_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
