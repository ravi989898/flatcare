<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * audit_logs only ever attributed an entry to a SuperAdmin
     * (super_admin_id). A Society Admin acting from the society portal -
     * e.g. Settings -> Permissions - has no row in that table, so this
     * plain text column carries the acting tenant user's name instead
     * (Society\PermissionSettingController sets it; super-admin-originated
     * rows leave it null and keep using the superAdmin relation).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('audit_logs', 'performed_by')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('performed_by')->nullable()->after('society_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('performed_by');
        });
    }
};
