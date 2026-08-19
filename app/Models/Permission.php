<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module_id',
    ];

    /**
     * Relationships
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function superAdmins(): BelongsToMany
    {
        return $this->belongsToMany(
            SuperAdmin::class,
            'super_admin_permissions',
            'permission_id',
            'super_admin_id'
        );
    }

    /**
     * Scopes
     */
    public function scopeByModule($query, int $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }
}
