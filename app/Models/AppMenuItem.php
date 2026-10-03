<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog of the mobile app's own navigation items (the hardcoded
 * _MenuItem/_MenuGroup list in mobile/lib/features/home/screens/
 * home_screen.dart), grouped the same way the app's home screen groups
 * them. Unlike MenuItem (the web sidebar catalog) there's no global
 * Super Admin default layer here - a society's own Admin decides
 * visibility per role directly, via Society\AppPermissionSettingController
 * -> society_role_app_menu_item. An item with no override is visible by
 * default, matching what every resident's app already shows today.
 */
class AppMenuItem extends Model
{
    protected $connection = 'main';

    protected $fillable = [
        'key',
        'group_label',
        'label',
        'icon',
        'display_order',
    ];

    public function societyOverrides(): HasMany
    {
        return $this->hasMany(SocietyRoleAppMenuItem::class);
    }

    /**
     * The app menu items this role is allowed to see, in display order. An
     * item with no society override for this role is visible by default
     * (there's no global default layer the way MenuItem has Settings ->
     * Menu Settings), so an unconfigured role - or a society that hasn't
     * touched App Permission at all - sees everything, matching what every
     * resident's app already shows today. Read by Api\V1\Common\
     * AppMenuItemController for the mobile app's own menu screen.
     */
    public static function visibleForRole(?string $roleName, ?int $societyId): Collection
    {
        $items = static::orderBy('display_order')->get();

        if (!$roleName || !$societyId) {
            return $items;
        }

        $role = RoleDefinition::where('name', $roleName)->first();

        if (!$role) {
            return $items;
        }

        $overrides = SocietyRoleAppMenuItem::where('society_id', $societyId)
            ->where('role_definition_id', $role->id)
            ->pluck('is_visible', 'app_menu_item_id');

        if ($overrides->isEmpty()) {
            return $items;
        }

        return $items->filter(fn (self $item) => (bool) ($overrides[$item->id] ?? true))->values();
    }
}
