<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit row for a visitor status change — written only by
 * App\Services\VisitorWorkflow, inside the same transaction as the change.
 */
class VisitorStatusHistory extends Model
{
    protected $connection = 'society';

    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id',
        'from_status',
        'to_status',
        'action',
        'changed_by',
        'ip_address',
        'note',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
