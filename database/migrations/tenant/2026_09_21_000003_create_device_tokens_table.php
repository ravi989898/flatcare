<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FCM registration tokens, one row per (user, device). A user can have
     * several active rows (phone + tablet, or a re-installed app), and every
     * one receives a push. `token` is stored encrypted (it is a credential
     * for pushing to that device); `token_hash` is its SHA-256, used for
     * uniqueness and lookups since ciphertext cannot be compared. A token
     * FCM reports as invalid is deactivated, not deleted, so a delivery
     * failure can still be traced.
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('token');
            $table->char('token_hash', 64)->unique();
            $table->string('platform', 20)->nullable(); // android | ios | web
            $table->string('device_name')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivation_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
