<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Day/night shift roster with real history: security_guard_logs records
     * one row per (guard, shift) duty period - started_at when they were
     * put on that shift, ended_at when someone else replaced them or they
     * were deactivated (null ended_at = still on duty right now). This is
     * what lets "who was on night duty on 5 Sep?" be answered later, rather
     * than only ever knowing who's on duty today.
     */
    public function up(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->string('shift')->nullable()->after('phone'); // day|night
        });

        Schema::create('security_guard_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_guard_id')->constrained('security_guards')->cascadeOnDelete();
            $table->string('shift'); // day|night
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            // The current holder of a shift is the row with ended_at still
            // null - looked up constantly (every "who's on duty" render),
            // so it needs an index.
            $table->index(['shift', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_guard_logs');

        Schema::table('security_guards', function (Blueprint $table) {
            $table->dropColumn('shift');
        });
    }
};
