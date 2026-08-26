@extends('society.layout')

@section('title', 'Polls & Surveys')

@php
    $statusBadge = ['draft' => 'secondary', 'open' => 'success', 'closed' => 'dark'];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Polls & Surveys</h1>
            <p class="text-muted mb-0">Ask residents a question and see live results.</p>
        </div>
        <a href="{{ route('society.polls.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> New Poll
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($polls->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Status</th>
                                <th>Options</th>
                                <th>Votes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($polls as $poll)
                                <tr>
                                    <td>{{ $poll->question }}</td>
                                    <td><span class="badge bg-{{ $statusBadge[$poll->status] }}">{{ ucfirst($poll->status) }}</span></td>
                                    <td>{{ $poll->options->count() }}</td>
                                    <td>{{ $poll->options->sum('votes_count') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('society.polls.show', $poll->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-bar-chart-steps fs-1 d-block mb-2"></i>
                    <p class="mb-2">No polls created yet.</p>
                    <a href="{{ route('society.polls.create') }}" class="btn btn-brand btn-sm">Create the first poll</a>
                </div>
            @endif
        </div>
    </div>

    @if ($polls->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $polls->links() }}
        </div>
    @endif
@stop
