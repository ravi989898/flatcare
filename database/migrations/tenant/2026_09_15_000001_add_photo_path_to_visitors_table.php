<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A photo taken at the gate (Api\V1\Guard\VisitorController::store) —
     * optional, camera-only from the app so the guard can't just pick an
     * old gallery photo. Mirrors security_guards.photo_path
     * (2026_09_12_000001_create_security_guards_table.php).
     */
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
