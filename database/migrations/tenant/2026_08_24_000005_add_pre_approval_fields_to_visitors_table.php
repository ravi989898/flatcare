<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds resident self-service pre-approval to the existing gate
     * register (see visitors table's original docblock, which explicitly
     * left this for later) rather than a separate gate_passes table: a
     * pending row here becomes the same row the gate desk checks in later,
     * so there's one record per visitor, not two that have to be
     * reconciled. `pending` is what a resident-created "Invite Visitor" /
     * "Gate Pass" starts as; the existing gate-side check-in flow
     * (Society\VisitorController::store()) still creates rows straight
     * into checked_in for a walk-in the resident never pre-approved.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE visitors MODIFY status ENUM('pending', 'checked_in', 'checked_out', 'denied') NOT NULL DEFAULT 'checked_in'");

        Schema::table('visitors', function (Blueprint $table) {
            $table->timestamp('expected_at')->nullable()->after('check_in_at');
            $table->foreignId('invited_by_user_id')->nullable()->after('checked_in_by')
                ->constrained('users')->nullOnDelete();
            $table->string('pass_code', 8)->nullable()->after('invited_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by_user_id');
            $table->dropColumn(['expected_at', 'pass_code']);
        });

        DB::statement("ALTER TABLE visitors MODIFY status ENUM('checked_in', 'checked_out', 'denied') NOT NULL DEFAULT 'checked_in'");
    }
};
