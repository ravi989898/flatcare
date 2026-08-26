<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Committee Members (mockup's Community tab) is deliberately two plain
     * columns on users rather than a new table or the existing roles/
     * permissions RBAC system — roles model permissions, not a display
     * title, and nothing seeds committee roles today. A resident with a
     * non-null committee_position shows up in the Committee Members list,
     * ordered by committee_order (e.g. Chairman before Secretary).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('committee_position')->nullable()->after('status'); // e.g. "Chairman", "Secretary", "Treasurer", "Member"
            $table->unsignedInteger('committee_order')->default(0)->after('committee_position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['committee_position', 'committee_order']);
        });
    }
};
