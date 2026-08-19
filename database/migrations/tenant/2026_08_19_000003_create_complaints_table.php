<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Complaints module — tenant database. Deliberately a separate table
     * from maintenance_requests even though the workflow shape is similar:
     * complaints are about people/conduct/society-rules (noise, parking
     * disputes, staff behavior…), not physical repairs, so they carry a
     * different category set and an optional "against" field maintenance
     * requests have no use for, and their lifecycle includes a "rejected"
     * outcome that doesn't make sense for a repair ticket.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flat_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('category', [
                'noise', 'parking', 'security', 'staff_behavior',
                'cleanliness', 'rule_violation', 'other',
            ])->default('other');

            $table->string('subject');
            $table->text('description');
            $table->string('against')->nullable(); // e.g. "Flat B-203", "Security guard"

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_review', 'resolved', 'closed', 'rejected'])->default('open');

            $table->string('raised_by_name');
            $table->string('raised_by_phone')->nullable();
            $table->foreignId('raised_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->json('history')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
