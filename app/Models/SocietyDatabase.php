<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocietyDatabase extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'society_id',
        'db_host',
        'db_port',
        'db_name',
        'db_user',
        'db_password', // Encrypted
        'db_charset',
        'db_collation',
        'status',
        'error_message',
        'last_migrated_at',
    ];

    protected $casts = [
        'last_migrated_at' => 'datetime',
    ];

    protected $hidden = [
        'db_password', // Never expose password
    ];

    /**
     * Relationships
     */
    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Methods
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isReady(): bool
    {
        return $this->status === 'active' && $this->last_migrated_at !== null;
    }
}
