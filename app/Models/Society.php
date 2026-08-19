<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Society extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'main';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'registration_number',
        'logo_path',
        'total_flats',
        'total_blocks',
        'start_date',
        'end_date',
        'status',
        'is_trial',
        'payment_verified',
        'db_name',
        'admin_name',
        'admin_email',
        'admin_phone',
        'settings',
        'created_by_super_admin',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_trial' => 'boolean',
        'payment_verified' => 'boolean',
        'settings' => 'array',
    ];

    protected $appends = [
        'is_active',
        'is_expired',
        'days_remaining',
    ];

    /**
     * Relationships
     */
    public function database(): HasOne
    {
        return $this->hasOne(SocietyDatabase::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(SocietyModule::class);
    }

    public function superAdmin()
    {
        return $this->belongsTo(SuperAdmin::class, 'created_by_super_admin');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Accessors
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->isWithinAccessPeriod();
    }

    public function getIsExpiredAttribute(): bool
    {
        return today() > $this->end_date;
    }

    public function getDaysRemainingAttribute(): int
    {
        return today()->diffInDays($this->end_date, false);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', today())
            ->where('end_date', '>=', today());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', today());
    }

    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    /**
     * Methods
     */
    public function isWithinAccessPeriod(): bool
    {
        $today = today();
        return $today >= $this->start_date && $today <= $this->end_date;
    }

    public function isModuleEnabled(string $moduleName): bool
    {
        return $this->modules()
            ->whereHas('module', function ($query) use ($moduleName) {
                $query->where('name', $moduleName);
            })
            ->where('is_enabled', true)
            ->exists();
    }

    public function getDatabaseCredentials()
    {
        return $this->database;
    }
}
