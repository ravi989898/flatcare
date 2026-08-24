<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Start free trial" on the public landing page doesn't self-register an
     * account — it captures a lead here for the Super Admin to review and
     * follow up with (create the society + invite them once qualified).
     * Main database.
     */
    public function up(): void
    {
        Schema::create('trial_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('society_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
            $table->string('status')->default('new'); // new, contacted, converted, dismissed
            $table->text('notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->foreignId('contacted_by_super_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_inquiries');
    }
};
