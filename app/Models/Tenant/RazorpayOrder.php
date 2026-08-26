<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per Razorpay order this backend has created for a bill — see
 * App\Services\RazorpayService for why this table exists: it's what lets
 * pay/verify trust a server-computed amount instead of anything the app
 * sends back, and it's the idempotency record that makes replaying a
 * captured payment a no-op.
 */
class RazorpayOrder extends Model
{
    protected $connection = 'society';

    public const STATUSES = ['created', 'processing', 'paid', 'failed'];

    protected $fillable = [
        'bill_id',
        'razorpay_order_id',
        'amount',
        'currency',
        'status',
        'razorpay_payment_id',
        'razorpay_signature',
        'payment_id',
        'initiated_by_user_id',
        'verified_at',
        'failure_reason',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(MaintenanceBill::class, 'bill_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
}
