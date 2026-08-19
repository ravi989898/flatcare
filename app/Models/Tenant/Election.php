<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Election extends Model
{
    use SoftDeletes;

    protected $connection = 'society';

    public const STATUSES = ['draft', 'nominations_open', 'voting_open', 'closed', 'cancelled'];

    protected $fillable = [
        'title',
        'description',
        'nomination_start_at',
        'nomination_end_at',
        'voting_start_at',
        'voting_end_at',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'nomination_start_at' => 'datetime',
        'nomination_end_at' => 'datetime',
        'voting_start_at' => 'datetime',
        'voting_end_at' => 'datetime',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(ElectionCandidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function acceptingNominations(): bool
    {
        return $this->status === 'nominations_open';
    }

    public function votingOpen(): bool
    {
        return $this->status === 'voting_open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * The candidate(s) with the most votes, once the election is closed.
     * Returns a collection because ties are possible.
     */
    public function winners()
    {
        $topVotes = $this->candidates->max('votes_count');

        if (!$topVotes) {
            return $this->candidates->take(0);
        }

        return $this->candidates->where('votes_count', $topVotes);
    }
}
