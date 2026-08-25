<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Society-vetted service providers (plumber, electrician, carpenter,
     * ...) residents can browse and call directly — distinct from the
     * resident Directory and from Emergency Contacts (life/safety numbers),
     * same shape as emergency_contacts since both are "a short admin-
     * managed list of name + phone to call".
     */
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('service_type'); // e.g. "Plumber", "Electrician", "Carpenter"
            $table->string('phone');
            $table->string('notes')->nullable(); // e.g. "Available 9am-6pm", a rating note, etc.
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('service_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
