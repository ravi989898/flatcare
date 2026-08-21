<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiPasswordReset extends Model
{
    protected $connection = 'main';

    protected $fillable = [
        'email',
        'society_id',
        'token_hash',
        'expires_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
