<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-resident notification feed for the mobile app. Named
     * resident_notifications (not "notifications") to avoid colliding with
     * Laravel's own conventional table name if the polymorphic Notifiable
     * system is ever wired up later — nothing in this codebase uses that
     * today (see AuthenticateApiToken / Tenant\User, neither has
     * Notifiable), so a plain FK'd table matches the rest of this schema's
     * style better than the polymorphic morph columns would.
     */
    public function up(): void
    {
        Schema::create('resident_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('type', [
                'maintenance_due', 'request_status', 'new_notice', 'visitor_arrived', 'event_reminder',
            ]);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable(); // e.g. {"bill_id": 12} so the app can deep-link on tap

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_notifications');
    }
};
