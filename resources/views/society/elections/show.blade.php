@extends('society.layout')

@section('title', $election->title)

@php
    $statusBadge = [
        'draft' => 'secondary', 'nominations_open' => 'info', 'voting_open' => 'success',
        'closed' => 'dark', 'cancelled' => 'danger',
    ];
    $nextSteps = [
        'draft' => ['nominations_open' => 'Open Nominations'],
        'nominations_open' => ['voting_open' => 'Start Voting'],
        'voting_open' => ['closed' => 'Close Election & Publish Results'],
    ];
    $totalVotes = $election->candidates->sum('votes_count');
    $winners = $election->isClosed() ? $election->winners() : collect();
@endphp

@section('content')
    <div class="mb-4">
        <a href="{{ route('society.elections.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Elections
        </a>
        <div class="d-flex align-items-center justify-content-between mt-2">
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 mb-0">{{ $election->title }}</h1>
                <span class="badge bg-{{ $statusBadge[$election->status] }}">{{ ucfirst(str_replace('_', ' ', $election->status)) }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('society.elections.edit', $election->id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
                @foreach ($nextSteps[$election->status] ?? [] as $nextStatus => $label)
                    <form action="{{ route('society.elections.status', $election->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                        <button type="submit" class="btn btn-sm btn-brand">{{ $label }}</button>
                    </form>
                @endforeach
                @if (!in_array($election->status, ['closed', 'cancelled']))
                    <form action="{{ route('society.elections.status', $election->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this election?')">Cancel</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if ($election->description)
        <p class="text-muted">{{ $election->description }}</p>
    @endif

    @if ($election->isClosed() && $winners->count() > 0)
        <div class="card stat-card mb-3 border-success">
            <div class="card-body p-4">
                <h5 class="mb-2"><i class="bi bi-trophy-fill text-warning"></i> {{ $winners->count() > 1 ? 'Winners (tied)' : 'Winner' }}</h5>
                @foreach ($winners as $winner)
                    <div class="fw-semibold">{{ $winner->user->name }} — {{ $winner->votes_count }} vote{{ $winner->votes_count === 1 ? '' : 's' }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card stat-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Candidates ({{ $election->candidates->count() }})</strong>
                    @if ($totalVotes > 0)
                        <span class="text-muted small">{{ $totalVotes }} vote{{ $totalVotes === 1 ? '' : 's' }} cast</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($election->candidates->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach ($election->candidates->sortByDesc('votes_count') as $candidate)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold">{{ $candidate->user->name }}</div>
                                            @if ($candidate->manifesto)
                                                <div class="text-muted small">{{ Str::limit($candidate->manifesto, 150) }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            @if ($election->votingOpen() || $election->isClosed())
                                                <span class="badge bg-brand text-white" style="background:var(--brand)">{{ $candidate->votes_count }} vote{{ $candidate->votes_count === 1 ? '' : 's' }}</span>
                                            @endif
                                            @if (in_array($election->status, ['draft', 'nominations_open']) && $candidate->votes_count === 0)
                                                <form action="{{ route('society.elections.candidates.destroy', [$election->id, $candidate->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this candidate?')">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted p-3 mb-0">No candidates yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if (in_array($election->status, ['draft', 'nominations_open']))
                <div class="card stat-card mb-3">
                    <div class="card-header bg-white"><strong>Add Candidate</strong></div>
                    <div class="card-body">
                        @if ($eligibleCandidates->count() > 0)
                            <form action="{{ route('society.elections.candidates.store', $election->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Resident</label>
                                    <select name="user_id" id="user_id" class="form-select" required>
                                        <option value="">— Select a resident —</option>
                                        @foreach ($eligibleCandidates as $resident)
                                            <option value="{{ $resident->id }}">{{ $resident->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="manifesto" class="form-label">Manifesto</label>
                                    <textarea name="manifesto" id="manifesto" rows="3" class="form-control" placeholder="Optional"></textarea>
                                </div>
                                <button type="submit" class="btn btn-brand w-100"><i class="bi bi-person-plus"></i> Add Candidate</button>
                            </form>
                        @else
                            <p class="text-muted mb-0 small">All active residents are already standing.</p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($election->votingOpen())
                <div class="card stat-card">
                    <div class="card-header bg-white"><strong>Record a Vote</strong></div>
                    <div class="card-body">
                        @if ($election->candidates->count() === 0)
                            <p class="text-muted mb-0 small">No candidates to vote for yet.</p>
                        @elseif ($eligibleVoters->count() > 0)
                            <form action="{{ route('society.elections.vote', $election->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="voter_user_id" class="form-label">Voter</label>
                                    <select name="voter_user_id" id="voter_user_id" class="form-select" required>
                                        <option value="">— Select a resident —</option>
                                        @foreach ($eligibleVoters as $voter)
                                            <option value="{{ $voter->id }}">{{ $voter->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="candidate_id" class="form-label">Vote For</label>
                                    <select name="candidate_id" id="candidate_id" class="form-select" required>
                                        <option value="">— Select a candidate —</option>
                                        @foreach ($election->candidates as $candidate)
                                            <option value="{{ $candidate->id }}">{{ $candidate->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-brand w-100"><i class="bi bi-check2-square"></i> Record Vote</button>
                            </form>
                        @else
                            <p class="text-muted mb-0 small">All active residents have already voted.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop
