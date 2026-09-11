<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The resident-facing mobile app logs in by mobile number + OTP rather
     * than email/password (see Api\V1\Auth\OtpAuthController) — a super
     * admin sets this directly on the flat, ahead of any resident account
     * existing, and the first successful OTP login auto-provisions the
     * resident's user record and links it here.
     */
    public function up(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->string('mobile_number')->nullable()->unique()->after('flat_number');
        });
    }

    public function down(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->dropColumn('mobile_number');
        });
    }
};
