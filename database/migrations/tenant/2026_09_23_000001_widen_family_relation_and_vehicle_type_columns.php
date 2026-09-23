<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * family_members.relation and vehicles.vehicle_type started life as
     * narrow enums ('spouse','child',... / 'car','bike',...), but the app
     * now offers a fixed-but-longer list (Father, Mother, Wife, ... /
     * Car, Bike, Auto, Van, Truck, ...) plus a free-text "Other" — any value
     * outside the old enum failed the INSERT, which is why Add Family Member
     * (and Add Vehicle for Auto/Van/Truck) broke. Widening them to VARCHAR
     * keeps every existing value as-is. Raw ALTERs for the same reason as
     * 2026_08_26_000002: doctrine/dbal isn't installed.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE family_members MODIFY relation VARCHAR(100) NOT NULL DEFAULT 'other'");
        DB::statement("ALTER TABLE vehicles MODIFY vehicle_type VARCHAR(50) NOT NULL DEFAULT 'car'");
    }

    /**
     * Only safe while every row still holds one of the old enum values.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE family_members MODIFY relation ENUM('spouse', 'child', 'parent', 'sibling', 'other') NOT NULL DEFAULT 'other'");
        DB::statement("ALTER TABLE vehicles MODIFY vehicle_type ENUM('car', 'bike', 'scooter', 'cycle', 'commercial') NOT NULL DEFAULT 'car'");
    }
};
