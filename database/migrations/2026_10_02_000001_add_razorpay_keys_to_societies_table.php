<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each society collects maintenance into its own Razorpay account, so the
 * API keys live on the society (entered by the Super Admin - see
 * Admin\SocietyPaymentGatewayController). The secret is stored encrypted
 * with APP_KEY (Society casts it 'encrypted'). A `rzp_test_` key id means
 * test mode, `rzp_live_` real money.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->string('razorpay_key_id', 64)->nullable()->after('water_unit_rate');
            $table->text('razorpay_key_secret')->nullable()->after('razorpay_key_id');
        });
    }

    public function down(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropColumn(['razorpay_key_id', 'razorpay_key_secret']);
        });
    }
};
