@extends('society.layout')

@section('title', 'Elections')

@php
    $statusBadge = [
        'draft' => 'secondary', 'nominations_open' => 'info', 'voting_open' => 'success',
        'closed' => 'dark', 'cancelled' => 'danger',
    ];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Elections</h1>
            <p class="text-muted mb-0">Society committee elections</p>
        </div>
        <a href="{{ route('society.elections.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> New Election
        </a>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($elections->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Candidates</th>
                                <th>Votes Cast</th>
                                <th>Voting Window</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($elections as $election)
                                <tr>
                                    <td><a href="{{ route('society.elections.show', $election->id) }}" class="text-decoration-none">{{ $election->title }}</a></td>
                                    <td><span class="badge bg-{{ $statusBadge[$election->status] }}">{{ ucfirst(str_replace('_', ' ', $election->status)) }}</span></td>
                                    <td>{{ $election->candidates_count }}</td>
                                    <td>{{ $election->votes_count }}</td>
                                    <td class="text-muted small">
                                        @if ($election->voting_start_at)
                                            {{ $election->voting_start_at->format('d M Y') }}
                                            @if ($election->voting_end_at) &ndash; {{ $election->voting_end_at->format('d M Y') }} @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('society.elections.show', $election->id) }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-clipboard2-check fs-1 d-block mb-2"></i>
                    <p class="mb-2">No elections yet.</p>
                    <a href="{{ route('society.elections.create') }}" class="btn btn-brand btn-sm">Create the first one</a>
                </div>
            @endif
        </div>
    </div>

    @if ($elections->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $elections->links() }}
        </div>
    @endif
@stop
