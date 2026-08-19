<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Election;
use App\Models\Tenant\ElectionCandidate;
use App\Models\Tenant\ElectionVote;
use App\Models\Tenant\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ElectionController extends Controller
{
    /**
     * Status transitions allowed from each status. 'cancelled' is reachable
     * from anything still in progress; there's no way back out of
     * 'closed'/'cancelled' — those are terminal.
     */
    private const TRANSITIONS = [
        'draft' => ['nominations_open', 'cancelled'],
        'nominations_open' => ['voting_open', 'cancelled'],
        'voting_open' => ['closed', 'cancelled'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function index(): View
    {
        $elections = Election::withCount('candidates', 'votes')->latest()->paginate(10);

        return view('society.elections.index', compact('elections'));
    }

    public function create(): View
    {
        return view('society.elections.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateElection($request);

        $election = Election::create([
            ...$validated,
            'status' => 'draft',
            'created_by_user_id' => Auth::guard('society')->id(),
        ]);

        return redirect()
            ->route('society.elections.show', $election->id)
            ->with('success', 'Election created as a draft.');
    }

    public function show(int $id): View
    {
        $election = Election::with(['candidates.user', 'votes'])->findOrFail($id);
        $residents = User::active()->orderBy('name')->get();

        // Residents who haven't voted yet, for the record-vote dropdown.
        $votedUserIds = $election->votes->pluck('voter_user_id');
        $eligibleVoters = $residents->whereNotIn('id', $votedUserIds);

        // Residents not already standing, for the add-candidate dropdown.
        $candidateUserIds = $election->candidates->pluck('user_id');
        $eligibleCandidates = $residents->whereNotIn('id', $candidateUserIds);

        return view('society.elections.show', compact('election', 'eligibleVoters', 'eligibleCandidates'));
    }

    public function edit(int $id): View
    {
        $election = Election::findOrFail($id);

        return view('society.elections.edit', compact('election'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $election = Election::findOrFail($id);
        $validated = $this->validateElection($request);

        $election->update($validated);

        return redirect()
            ->route('society.elections.show', $election->id)
            ->with('success', 'Election details updated.');
    }

    /**
     * Move an election to its next status (or cancel it).
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $election = Election::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Election::STATUSES),
        ]);

        $allowed = self::TRANSITIONS[$election->status] ?? [];

        if (!in_array($validated['status'], $allowed, true)) {
            return back()->with('error', "Can't move an election from \"{$election->status}\" to \"{$validated['status']}\".");
        }

        $election->update(['status' => $validated['status']]);

        return back()->with('success', 'Election status updated.');
    }

    /**
     * Nominate a resident as a candidate.
     */
    public function addCandidate(Request $request, int $id): RedirectResponse
    {
        $election = Election::findOrFail($id);

        if (!in_array($election->status, ['draft', 'nominations_open'], true)) {
            return back()->with('error', 'Nominations are closed for this election.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'manifesto' => 'nullable|string|max:2000',
        ]);

        try {
            ElectionCandidate::create([
                'election_id' => $election->id,
                'user_id' => $validated['user_id'],
                'manifesto' => $validated['manifesto'] ?? null,
            ]);
        } catch (QueryException) {
            return back()->with('error', 'That resident is already standing in this election.');
        }

        return back()->with('success', 'Candidate added.');
    }

    /**
     * Withdraw a candidate — only while nominations are still open and no
     * votes have been cast for them yet.
     */
    public function removeCandidate(int $id, int $candidateId): RedirectResponse
    {
        $election = Election::findOrFail($id);
        $candidate = ElectionCandidate::where('election_id', $election->id)->findOrFail($candidateId);

        if (!in_array($election->status, ['draft', 'nominations_open'], true)) {
            return back()->with('error', 'Candidates can only be removed before voting opens.');
        }

        if ($candidate->votes_count > 0) {
            return back()->with('error', 'This candidate already has votes recorded and cannot be removed.');
        }

        $candidate->delete();

        return back()->with('success', 'Candidate removed.');
    }

    /**
     * Record a resident's vote. Since there's no resident self-service
     * portal yet, the admin/committee records this on the voter's behalf
     * (e.g. at a physical polling desk) — the unique constraint on
     * election_votes still guarantees one vote per resident.
     */
    public function vote(Request $request, int $id): RedirectResponse
    {
        $election = Election::findOrFail($id);

        if (!$election->votingOpen()) {
            return back()->with('error', 'Voting is not currently open for this election.');
        }

        $validated = $request->validate([
            'candidate_id' => 'required|exists:election_candidates,id',
            'voter_user_id' => 'required|exists:users,id',
        ]);

        try {
            DB::connection('society')->transaction(function () use ($election, $validated) {
                ElectionVote::create([
                    'election_id' => $election->id,
                    'candidate_id' => $validated['candidate_id'],
                    'voter_user_id' => $validated['voter_user_id'],
                ]);

                ElectionCandidate::where('id', $validated['candidate_id'])->increment('votes_count');
            });
        } catch (QueryException) {
            return back()->with('error', 'This resident has already voted in this election.');
        }

        return back()->with('success', 'Vote recorded.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateElection(Request $request): array
    {
        foreach (['description', 'nomination_start_at', 'nomination_end_at', 'voting_start_at', 'voting_end_at'] as $field) {
            if ($request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nomination_start_at' => 'nullable|date',
            'nomination_end_at' => 'nullable|date|after_or_equal:nomination_start_at',
            'voting_start_at' => 'nullable|date',
            'voting_end_at' => 'nullable|date|after_or_equal:voting_start_at',
        ]);
    }
}
