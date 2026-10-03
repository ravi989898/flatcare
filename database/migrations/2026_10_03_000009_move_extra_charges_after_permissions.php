<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reorders the Society-portal sidebar: "Extra Charges" moves from right
 * after Payments to right after Permissions, per the Society Admin's
 * request. Every item between its old and new position shifts up one slot
 * to close the gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        $order = [
            'dashboard' => 1,
            'admins' => 2,
            'payments' => 3,
            'water-readings' => 4,
            'blocks' => 5,
            'security' => 6,
            'visitors' => 7,
            'reports' => 8,
            'complaints' => 9,
            'permissions' => 10,
            'extra-charges' => 11,
        ];

        foreach ($order as $key => $position) {
            DB::table('menu_items')->where('key', $key)->update(['display_order' => $position]);
        }
    }

    public function down(): void
    {
        $order = [
            'dashboard' => 1,
            'admins' => 2,
            'payments' => 3,
            'extra-charges' => 4,
            'water-readings' => 5,
            'blocks' => 6,
            'security' => 7,
            'visitors' => 8,
            'reports' => 9,
            'complaints' => 10,
            'permissions' => 11,
        ];

        foreach ($order as $key => $position) {
            DB::table('menu_items')->where('key', $key)->update(['display_order' => $position]);
        }
    }
};
