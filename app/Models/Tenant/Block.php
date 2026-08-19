<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Block extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    protected $fillable = [
        'name',
        'block_number',
        'description',
        'total_flats',
        'total_floors',
        'block_admin_contact',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function flats(): HasMany
    {
        return $this->hasMany(Flat::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
