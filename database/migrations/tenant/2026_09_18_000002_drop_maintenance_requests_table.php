<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Maintenance Requests feature (society.maintenance.* routes,
     * MaintenanceController, MaintenanceRequest model) has been removed
     * entirely — this drops its table from every tenant database. down()
     * recreates the exact schema from 2026_08_19_000001_create_maintenance_
     * requests_table + 2026_08_24_000007_add_civil_work_category_to_
     * maintenance_requests, though any row data is unrecoverable once up()
     * has run.
     */
    public function up(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }

    public function down(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('flat_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('category', [
                'plumbing', 'electrical', 'carpentry', 'painting',
                'cleaning', 'security', 'lift', 'common_area', 'civil_work', 'other',
            ])->default('other');

            $table->string('title');
            $table->text('description');

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed', 'cancelled'])->default('open');

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
            $table->index(['flat_id', 'status']);
        });
    }
};
