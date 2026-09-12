<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional last-4-digits-of-Aadhaar record for a security guard — an
     * identity reference for the society office, never the full number.
     */
    public function up(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->string('aadhar_last4', 4)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->dropColumn('aadhar_last4');
        });
    }
};
