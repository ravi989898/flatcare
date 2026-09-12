<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Society security personnel, managed by the platform (master) admin
     * from the Societies list, same as Admins/Blocks. A society can have
     * more than one guard on record over time (shift changes, replacements);
     * only the most recently created Active one is what the resident mobile
     * app shows as "on duty". There is no delete — a guard who has left is
     * simply marked Inactive, keeping history intact.
     */
    public function up(): void
    {
        Schema::create('security_guards', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('phone');
            $table->string('photo_path')->nullable();
            $table->string('status')->default('active'); // active|inactive

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_guards');
    }
};
