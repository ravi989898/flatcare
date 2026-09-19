<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Visitor > Settings toggles are per household, so they live on the
     * flat: "allow guests only if I approve" makes the gate raise an entry
     * request for every guest, and "house closed" stops walk-ins entirely.
     */
    public function up(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->boolean('guest_approval_required')->default(false);
            $table->boolean('house_closed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->dropColumn(['guest_approval_required', 'house_closed']);
        });
    }
};
