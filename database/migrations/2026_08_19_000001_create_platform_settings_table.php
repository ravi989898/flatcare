<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform-wide branding/settings — a singleton row (id = 1), main
     * database. Kept as plain columns rather than a key-value table since
     * there's currently exactly one setting (the logo); a key-value shape
     * can be introduced later if/when more settings need it without this
     * table getting in the way.
     */
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->foreignId('updated_by_super_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
