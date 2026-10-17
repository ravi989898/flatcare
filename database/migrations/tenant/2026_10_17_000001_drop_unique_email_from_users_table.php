<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Residents don't log in with email/password (OTP against flats.mobile_number
 * is the login identifier - see Api\V1\Auth\OtpAuthController), so two
 * residents sharing an email address (e.g. a family that only gives out one
 * shared address) shouldn't be blocked. phone stays unique: it's still the
 * OTP identifier and the admin UI's uniqueness guarantees depend on it (see
 * Society\BlockController::syncResident()). The plain (non-unique) index on
 * email from the original migration is untouched, so email lookups
 * (TenantAccountLocator, password reset) stay indexed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
