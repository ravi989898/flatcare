<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The browser-tab/app icon, uploaded the same way as the logo — kept as
     * a separate column (rather than reusing logo_path) since the two are
     * cropped and rendered completely differently: the logo is shown wide
     * in navigation bars, the icon is always square.
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('icon_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('icon_path');
        });
    }
};
