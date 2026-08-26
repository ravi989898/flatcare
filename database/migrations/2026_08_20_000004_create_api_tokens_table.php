<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bearer tokens for the mobile API, stored in the MAIN database.
     *
     * Tenant users live on a per-society database that isn't known until a
     * token is resolved, so a Sanctum-style "ask the tokenable model's own
     * connection" lookup doesn't work here — the token has to be resolvable
     * from a single, always-reachable table first. This table is that: it
     * maps a hashed bearer token straight to the society + tenant user id,
     * mirroring the token structure sketched in ARCHITECTURE.md §9.2.
     */
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained()->cascadeOnDelete();
            // No FK: tenant_user_id refers to a row in that society's own
            // (separate) database, which this database can't reference.
            $table->unsignedBigInteger('tenant_user_id');
            $table->string('role_name')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->string('device_id')->nullable();
            $table->string('device_platform')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['society_id', 'tenant_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
