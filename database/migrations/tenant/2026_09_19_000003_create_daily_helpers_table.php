<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A resident's regular helpers (maid, cook, driver...) so the household
     * keeps one list of them in the app. Attached to the flat, not the user,
     * so everyone living there sees the same list.
     */
    public function up(): void
    {
        Schema::create('daily_helpers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('phone', 20);
            $table->string('helper_type', 30)->default('maid');
            $table->string('photo_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('flat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_helpers');
    }
};
