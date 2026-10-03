<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors menu_items/society_role_menu_item but for the mobile app's own
 * navigation (mobile/lib/features/home/screens/home_screen.dart's hardcoded
 * menu groups) rather than the Society-portal sidebar. Lets a society's
 * Admin choose which app menu items its Treasurer/Vice Chairman/Secretary/
 * Committee Member/Resident see, the same way society_role_menu_item does
 * for the web portal - see Society\AppPermissionSettingController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group_label');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('society_role_app_menu_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained('societies')->cascadeOnDelete();
            $table->foreignId('role_definition_id')->constrained('role_definitions')->cascadeOnDelete();
            $table->foreignId('app_menu_item_id')->constrained('app_menu_items')->cascadeOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['society_id', 'role_definition_id', 'app_menu_item_id'], 'society_role_app_menu_item_unique');
        });

        // One row per item in each _MenuGroup of home_screen.dart, so the
        // catalog here matches exactly what a resident's app can show.
        $items = [
            ['group' => 'Society Admin', 'key' => 'app-water-readings', 'label' => 'Water Readings', 'icon' => '💧'],
            ['group' => 'Society Admin', 'key' => 'app-payment-status', 'label' => 'Payment Status', 'icon' => '💰'],
            ['group' => 'Quick Access', 'key' => 'app-my-bills', 'label' => 'My Bills', 'icon' => '🧾'],
            ['group' => 'Quick Access', 'key' => 'app-complaints', 'label' => 'Complaints', 'icon' => '⚠️'],
            ['group' => 'Directory', 'key' => 'app-members', 'label' => 'Members', 'icon' => '👥'],
            ['group' => 'Directory', 'key' => 'app-committee-members', 'label' => 'Committee Members', 'icon' => '🧑‍💼'],
            ['group' => 'Directory', 'key' => 'app-family-members', 'label' => 'Family Members', 'icon' => '👪'],
            ['group' => 'Directory', 'key' => 'app-vehicles', 'label' => 'Vehicles', 'icon' => '🚗'],
            ['group' => 'Directory', 'key' => 'app-important-contacts', 'label' => 'Important Contacts', 'icon' => '🚨'],
            ['group' => 'Directory', 'key' => 'app-service-providers', 'label' => 'Service Providers', 'icon' => '🛠️'],
            ['group' => 'Interaction', 'key' => 'app-meetings', 'label' => 'Meetings', 'icon' => '💬'],
            ['group' => 'Interaction', 'key' => 'app-announcement', 'label' => 'Announcement', 'icon' => '📣'],
            ['group' => 'Interaction', 'key' => 'app-event', 'label' => 'Event', 'icon' => '📅'],
            ['group' => 'Interaction', 'key' => 'app-voting', 'label' => 'Voting', 'icon' => '🗳️'],
            ['group' => 'Interaction', 'key' => 'app-amenities', 'label' => 'Amenities', 'icon' => '📋'],
            ['group' => 'Interaction', 'key' => 'app-proposal', 'label' => 'Proposal', 'icon' => '📝'],
            ['group' => 'Interaction', 'key' => 'app-suggestions', 'label' => 'Suggestions', 'icon' => '💡'],
            ['group' => 'Interaction', 'key' => 'app-tasks', 'label' => 'Tasks', 'icon' => '🗒️'],
            ['group' => 'Interaction', 'key' => 'app-notifications', 'label' => 'Notifications', 'icon' => '🔔'],
            ['group' => 'Visitor', 'key' => 'app-my-visitors', 'label' => 'My Visitors', 'icon' => '🚪'],
            ['group' => 'Visitor', 'key' => 'app-my-daily-helpers', 'label' => 'My Daily Helpers', 'icon' => '🧹'],
            ['group' => 'Visitor', 'key' => 'app-gate-keeper', 'label' => 'Gate Keeper', 'icon' => '👮'],
            ['group' => 'Visitor', 'key' => 'app-gate-pass', 'label' => 'Gate Pass', 'icon' => '🎫'],
            ['group' => 'Visitor', 'key' => 'app-visitor-settings', 'label' => 'Settings', 'icon' => '⚙️'],
            ['group' => 'My Building', 'key' => 'app-documents', 'label' => 'Documents', 'icon' => '📄'],
            ['group' => 'My Building', 'key' => 'app-statistics', 'label' => 'Statistics', 'icon' => '📊'],
        ];

        $order = 1;

        foreach ($items as $item) {
            DB::table('app_menu_items')->insertOrIgnore([
                'key' => $item['key'],
                'group_label' => $item['group'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'display_order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('society_role_app_menu_item');
        Schema::dropIfExists('app_menu_items');
    }
};
