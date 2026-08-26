<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Password-reset tokens for mobile app residents, in the MAIN database
     * for the same reason api_tokens is: the society isn't known until the
     * token/email pair resolves one.
     */
    public function up(): void
    {
        Schema::create('api_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('society_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'society_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_password_resets');
    }
};
