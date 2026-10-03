<?php

namespace App\Models;

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
}
