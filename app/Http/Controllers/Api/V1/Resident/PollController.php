<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\VotePollRequest;
use App\Http\Resources\Api\V1\PollResource;
use App\Models\Tenant\Poll;
use App\Models\Tenant\PollOption;
use App\Models\Tenant\PollVote;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PollController extends ApiController
{
    public function index(): JsonResponse
    {
        $polls = Poll::query()
            ->whereIn('status', ['open', 'closed'])
            ->with(['options', 'votes'])
            ->latest()
            ->get();

        $viewerId = $this->user()->id;

        return $this->ok($polls->map(fn ($poll) => new PollResource($poll, $viewerId)));
    }

    public function show(int $id): JsonResponse
    {
        $poll = Poll::with(['options', 'votes'])->whereIn('status', ['open', 'closed'])->findOrFail($id);

        return $this->ok(new PollResource($poll, $this->user()->id));
    }

    /**
     * One vote per resident per poll — same transaction + increment
     * pattern as Society\ElectionController::vote(), with the "already
     * voted" case caught via the poll_votes unique constraint rather than
     * a pre-check (avoids a race between the check and the insert).
     */
    public function vote(VotePollRequest $request, int $id): JsonResponse
    {
        $poll = Poll::findOrFail($id);

        if (!$poll->isOpen()) {
            return $this->fail('This poll is not open for voting.', 422);
        }

        $option = PollOption::where('poll_id', $poll->id)->findOrFail($request->integer('poll_option_id'));

        try {
            DB::transaction(function () use ($poll, $option) {
                PollVote::create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $option->id,
                    'voter_user_id' => $this->user()->id,
                ]);

                $option->increment('votes_count');
            });
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return $this->fail('You have already voted in this poll.', 422);
            }

            throw $e;
        }

        $poll->load(['options', 'votes']);

        return $this->ok(new PollResource($poll, $this->user()->id), 'Vote recorded successfully.');
    }
}
