<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a notification to the visitor request it is about and tracks
     * its push delivery, so a failed FCM send is recorded (and can be
     * retried with `php artisan notifications:retry-push`) without ever
     * affecting the in-app notification or the visitor row itself.
     *
     * `type` was an enum that predated the visitor request/approve/reject
     * notifications (they were being inserted with values outside it), so
     * it becomes a plain string.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE resident_notifications MODIFY type VARCHAR(40) NOT NULL');

        Schema::table('resident_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('society_id')->nullable()->after('user_id');
            $table->foreignId('visitor_id')->nullable()->after('society_id')->constrained('visitors')->cascadeOnDelete();

            // pending = queued, sent, failed, skipped (no active device / push disabled).
            $table->string('push_status', 10)->default('skipped')->after('read_at');
            $table->unsignedTinyInteger('push_attempts')->default(0)->after('push_status');
            $table->timestamp('push_sent_at')->nullable()->after('push_attempts');
            $table->text('push_error')->nullable()->after('push_sent_at');

            $table->index(['push_status', 'push_attempts']);
        });
    }

    public function down(): void
    {
        Schema::table('resident_notifications', function (Blueprint $table) {
            $table->dropIndex(['push_status', 'push_attempts']);
            $table->dropConstrainedForeignId('visitor_id');
            $table->dropColumn(['society_id', 'push_status', 'push_attempts', 'push_sent_at', 'push_error']);
        });

        DB::statement("ALTER TABLE resident_notifications MODIFY type ENUM('maintenance_due','request_status','new_notice','visitor_arrived','event_reminder') NOT NULL");
    }
};
