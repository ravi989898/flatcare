<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A short, admin-managed list (Security desk, Ambulance, Fire Brigade,
     * Police, ...) shown as one-tap-to-call tiles in the resident app —
     * society-wide, not per-flat, so no flat_id/user_id here.
     */
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();

            $table->string('label'); // e.g. "Security", "Ambulance"
            $table->string('phone');
            $table->string('type')->default('other'); // security|ambulance|fire|police|other — free string, not enum: the admin may add local ones
            $table->string('availability')->nullable(); // e.g. "24x7 Available"
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
