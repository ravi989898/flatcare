<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A "Start free trial" lead captured from the public landing page. Super
 * Admin reviews these and contacts the prospect directly (there is no
 * self-serve society signup — a society is always provisioned by Super
 * Admin from the admin panel).
 */
class TrialInquiry extends Model
{
    use HasFactory;

    protected $connection = 'main';

    protected $fillable = [
        'society_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'status',
        'notes',
        'contacted_at',
        'contacted_by_super_admin_id',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'contacted_by_super_admin_id');
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }
}
