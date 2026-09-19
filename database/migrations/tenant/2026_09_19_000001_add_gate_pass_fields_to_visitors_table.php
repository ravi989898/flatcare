<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The mobile app's "Gate Pass" form (visitor email, a From/To validity
     * window, an optional photo) and its quicker "Pre-Approval" form share
     * the one visitors row a resident invite already creates — `entry_kind`
     * only records which form made it, so the Pre-Approved Entry list can
     * show the pre-approvals without the dated gate passes.
     */
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('visitor_email')->nullable()->after('visitor_phone');
            $table->timestamp('valid_until')->nullable()->after('expected_at');
            $table->string('entry_kind', 20)->default('gate_pass')->after('pass_code');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['visitor_email', 'valid_until', 'entry_kind']);
        });
    }
};
