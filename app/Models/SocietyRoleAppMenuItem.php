<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A society's own override of one role's visibility for one app menu item
 * (see app_menu_items), set via Society\AppPermissionSettingController ->
 * Settings -> Permissions -> App Permission. Mirrors SocietyRoleMenuItem's
 * shape but for the mobile app's navigation instead of the web sidebar.
 */
class SocietyRoleAppMenuItem extends Model
{
    protected $connection = 'main';

    protected $table = 'society_role_app_menu_item';

    protected $fillable = [
        'society_id',
        'role_definition_id',
        'app_menu_item_id',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}
