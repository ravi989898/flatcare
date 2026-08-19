<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Events module — tenant database. Shaped like announcements (simple
     * lifecycle, no assignment/history) but scheduled: start_at drives the
     * upcoming/past split instead of a status filter, and cancellation is
     * a status rather than a soft delete so a cancelled event still shows
     * with an explanation instead of vanishing.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description');
            $table->enum('category', ['cultural', 'sports', 'meeting', 'festival', 'other'])->default('other');
            $table->string('location')->nullable();

            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();

            $table->enum('status', ['published', 'cancelled'])->default('published');
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
