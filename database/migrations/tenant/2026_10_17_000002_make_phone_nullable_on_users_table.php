<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lets a flat that has no mobile number yet still carry its own resident
 * account (name/email entered from the flat edit form - see
 * App\Services\FlatResidentSync). phone stays unique: MySQL's unique index
 * allows any number of NULLs, so it still guards the OTP identifier for
 * every resident that has one. Raw SQL keeps this free of doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection($this->getConnection())->statement('ALTER TABLE users MODIFY phone VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::connection($this->getConnection())->statement('ALTER TABLE users MODIFY phone VARCHAR(255) NOT NULL');
    }
};
