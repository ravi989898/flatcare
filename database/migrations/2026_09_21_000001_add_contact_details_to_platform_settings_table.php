<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public-site contact details (footer, contact page, structured data),
     * editable by a super admin. Null means "use the config/seo.php default".
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('icon_path');
            $table->string('contact_phone_1', 20)->nullable()->after('contact_email');
            $table->string('contact_phone_2', 20)->nullable()->after('contact_phone_1');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_email', 'contact_phone_1', 'contact_phone_2']);
        });
    }
};
