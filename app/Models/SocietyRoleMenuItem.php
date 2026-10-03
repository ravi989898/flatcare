<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-society override of role_menu_item (see the migration that created
 * this table). A row here means this society's Admin has explicitly
 * decided visibility for this role/menu item pair, via Society\
 * PermissionSettingController -> Settings -> Permissions in the society
 * portal; MenuItem::visibleForRole() prefers these over the global default
 * whenever the society has configured the role at all.
 */
class SocietyRoleMenuItem extends Model
{
    protected $connection = 'main';

    protected $table = 'society_role_menu_item';

    protected $fillable = [
        'society_id',
        'role_definition_id',
        'menu_item_id',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}
