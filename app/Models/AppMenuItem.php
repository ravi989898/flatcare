<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\User;
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
    /**
     * Society-admin features: hidden for every managed role until the
     * society's Admin ticks them in App Permission. Everything else is
     * visible by default. The Society Admin always sees all items.
     */
    public const HIDDEN_BY_DEFAULT = ['app-water-readings', 'app-payment-status'];

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

        $role = $roleName ? RoleDefinition::where('name', $roleName)->first() : null;

        $overrides = ($role && $societyId)
            ? SocietyRoleAppMenuItem::where('society_id', $societyId)
                ->where('role_definition_id', $role->id)
                ->pluck('is_visible', 'app_menu_item_id')
            : collect();

        return $items->filter(fn (self $item) => (bool) ($overrides[$item->id] ?? $item->isVisibleByDefault()))->values();
    }

    /**
     * Keys of the items this user may see in the app: everything for the
     * Society Admin, otherwise the union over all of the user's roles (a
     * Secretary who is also a Resident gets what either role allows).
     *
     * @return array<int, string>
     */
    public static function visibleKeysForUser(User $user, ?int $societyId): array
    {
        if ($user->hasRole('admin')) {
            return static::orderBy('display_order')->pluck('key')->all();
        }

        $roleNames = $user->roles()->pluck('name');

        if ($roleNames->isEmpty()) {
            return static::visibleForRole(null, $societyId)->pluck('key')->all();
        }

        return $roleNames
            ->flatMap(fn (string $role) => static::visibleForRole($role, $societyId)->pluck('key'))
            ->unique()
            ->values()
            ->all();
    }

    public function isVisibleByDefault(): bool
    {
        return !in_array($this->key, self::HIDDEN_BY_DEFAULT, true);
    }
}
