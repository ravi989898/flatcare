<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit trail of every visitor status change: who moved a
     * request from which status to which, and from where. Rows are never
     * updated or deleted (visitors are soft-deleted, so the history stays
     * even if a pass is cancelled).
     */
    public function up(): void
    {
        Schema::create('visitor_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable(); // null = the creation row
            $table->string('to_status', 20);
            $table->string('action', 30); // requested, approved, rejected, entered, exited
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('note')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['visitor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_status_histories');
    }
};
