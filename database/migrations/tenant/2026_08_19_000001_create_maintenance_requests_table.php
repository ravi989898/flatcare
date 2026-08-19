<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maintenance module — tenant database.
     *
     * A request may be tied to a specific flat, or left flat-less for
     * common-area issues (lobby lighting, lift, garden, etc.) — hence
     * flat_id/block_id are both nullable rather than one implying the other.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('flat_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('category', [
                'plumbing', 'electrical', 'carpentry', 'painting',
                'cleaning', 'security', 'lift', 'common_area', 'other',
            ])->default('other');

            $table->string('title');
            $table->text('description');

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed', 'cancelled'])->default('open');

            // Who raised it. Kept as free-text name/phone (rather than a
            // strict FK) because requests are logged by the admin on a
            // resident's behalf for now — there's no resident self-service
            // portal yet — but raised_by_user_id links it when the resident
            // is already a known tenant user.
            $table->string('raised_by_name');
            $table->string('raised_by_phone')->nullable();
            $table->foreignId('raised_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Lightweight status/assignment audit trail: [{at, status, note, by}, ...].
            // A full activity-log table is overkill for the current single-admin
            // workflow; this is enough to show "what happened when" on the
            // request detail page without adding another table + model.
            $table->json('history')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('category');
            $table->index(['flat_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
