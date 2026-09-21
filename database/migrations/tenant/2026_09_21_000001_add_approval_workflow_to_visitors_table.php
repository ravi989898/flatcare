<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real-time visitor approval. The workflow's PENDING -> APPROVED ->
     * ENTERED -> EXITED (or PENDING -> REJECTED) is layered onto the existing
     * visitors table rather than a second visitor_requests table, so there
     * is still one record per visitor: `checked_in` is ENTERED,
     * `checked_out` is EXITED and `denied` is REJECTED (check_in_at /
     * check_out_at are entered_at / exited_at). Only `approved` is a new
     * status - before this, approving a request skipped straight to
     * checked_in, so the gate had no explicit "resident said yes, now let
     * them in" step.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE visitors MODIFY status ENUM('pending', 'approved', 'checked_in', 'checked_out', 'denied') NOT NULL DEFAULT 'checked_in'");

        Schema::table('visitors', function (Blueprint $table) {
            // Denormalised from the flat so a request can be scoped/validated
            // by block without a join, and so it survives a flat move.
            $table->foreignId('block_id')->nullable()->after('flat_id')->constrained('blocks')->nullOnDelete();

            // The guard who raised the request - the one notified with the
            // resident's decision. checked_in_by gets overwritten by whichever
            // guard actually lets the visitor in, so it can't serve this role.
            $table->foreignId('gate_keeper_id')->nullable()->after('checked_out_by')->constrained('users')->nullOnDelete();

            $table->foreignId('approved_by')->nullable()->after('gate_keeper_id')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');

            $table->index(['flat_id', 'status']);
        });

        // Guard-raised requests created before this migration recorded the
        // guard in checked_in_by; carry it over so their decision still
        // reaches the right person.
        DB::statement('UPDATE visitors SET gate_keeper_id = checked_in_by WHERE invited_by_user_id IS NULL AND checked_in_by IS NOT NULL');
        DB::statement('UPDATE visitors v JOIN flats f ON f.id = v.flat_id SET v.block_id = f.block_id');
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['flat_id', 'status']);
            $table->dropConstrainedForeignId('block_id');
            $table->dropConstrainedForeignId('gate_keeper_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['approved_at', 'rejected_at']);
        });

        DB::statement("UPDATE visitors SET status = 'checked_in' WHERE status = 'approved'");
        DB::statement("ALTER TABLE visitors MODIFY status ENUM('pending', 'checked_in', 'checked_out', 'denied') NOT NULL DEFAULT 'checked_in'");
    }
};
