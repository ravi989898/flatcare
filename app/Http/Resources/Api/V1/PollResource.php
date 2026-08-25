<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\Poll
 */
class PollResource extends JsonResource
{
    public function __construct(private $poll, private ?int $viewerUserId = null)
    {
        parent::__construct($poll);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalVotes = $this->poll->options->sum('votes_count');
        $myVote = $this->viewerUserId
            ? $this->poll->votes->firstWhere('voter_user_id', $this->viewerUserId)
            : null;

        return [
            'id' => $this->poll->id,
            'question' => $this->poll->question,
            'description' => $this->poll->description,
            'status' => $this->poll->status,
            'closes_at' => $this->poll->closes_at?->toIso8601String(),
            'total_votes' => $totalVotes,
            'my_option_id' => $myVote?->poll_option_id,
            'options' => $this->poll->options->map(fn ($option) => [
                'id' => $option->id,
                'label' => $option->label,
                'votes_count' => $option->votes_count,
                'percentage' => $totalVotes > 0 ? round($option->votes_count / $totalVotes * 100) : 0,
            ]),
            'created_at' => $this->poll->created_at?->toIso8601String(),
        ];
    }
}
