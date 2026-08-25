@extends('society.layout')

@section('title', $poll->question)

@php
    $statusBadge = ['draft' => 'secondary', 'open' => 'success', 'closed' => 'dark'];
    $totalVotes = $poll->options->sum('votes_count');
@endphp

@section('content')
    <div class="mb-4">
        <a href="{{ route('society.polls.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Polls
        </a>
        <div class="d-flex align-items-center justify-content-between mt-2">
            <h1 class="h3 mb-0">{{ $poll->question }}</h1>
            <span class="badge bg-{{ $statusBadge[$poll->status] }}">{{ ucfirst($poll->status) }}</span>
        </div>
        @if ($poll->description)
            <p class="text-muted mt-2 mb-0">{{ $poll->description }}</p>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card mb-3">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <strong>Results</strong>
            <span class="text-muted small">{{ $totalVotes }} vote{{ $totalVotes === 1 ? '' : 's' }}</span>
        </div>
        <div class="card-body">
            @foreach ($poll->options as $option)
                @php $pct = $totalVotes > 0 ? round($option->votes_count / $totalVotes * 100) : 0; @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $option->label }}</span>
                        <span class="text-muted">{{ $option->votes_count }} ({{ $pct }}%)</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-brand" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-header bg-white"><strong>Manage</strong></div>
        <div class="card-body d-flex gap-2">
            @if ($poll->status !== 'open')
                <form action="{{ route('society.polls.status', $poll->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="open">
                    <button type="submit" class="btn btn-outline-success btn-sm">Open for Voting</button>
                </form>
            @endif
            @if ($poll->status !== 'closed')
                <form action="{{ route('society.polls.status', $poll->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="closed">
                    <button type="submit" class="btn btn-outline-dark btn-sm">Close Poll</button>
                </form>
            @endif
            <form action="{{ route('society.polls.destroy', $poll->id) }}" method="POST" onsubmit="return confirm('Delete this poll?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
@stop
