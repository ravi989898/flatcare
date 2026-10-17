@extends('society.layout')

@section('title', 'Elections')

@php
    $statusTone = [
        'draft' => 'neutral', 'nominations_open' => 'info', 'voting_open' => 'success',
        'closed' => 'neutral', 'cancelled' => 'danger',
    ];
@endphp

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Elections</h1>
            <p class="text-muted mb-0">Society committee elections</p>
        </div>
        <a href="{{ route('society.elections.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> New Election
        </a>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search election…">
            <x-fc.select name="status" placeholder="Any status"
                :options="collect(\App\Models\Tenant\Election::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])" />
        </x-fc.toolbar>

        @if ($elections->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="title">Election</x-fc.th>
                            <th>Status</th>
                            <x-fc.th sort="candidates" first="desc" class="num">Candidates</x-fc.th>
                            <x-fc.th sort="votes" first="desc" class="num">Votes Cast</x-fc.th>
                            <x-fc.th sort="voting" first="desc">Voting Window</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($elections as $election)
                            <tr>
                                <td>
                                    <a href="{{ route('society.elections.show', $election->id) }}" class="fc-strong">{{ $election->title }}</a>
                                    <span class="fc-sub">Created {{ $election->created_at->format('d M Y') }}</span>
                                </td>
                                <td><x-fc.pill :status="$election->status" :tone="$statusTone[$election->status] ?? 'neutral'" /></td>
                                <td class="num">{{ $election->candidates_count }}</td>
                                <td class="num">{{ $election->votes_count }}</td>
                                <td class="fc-nowrap">
                                    @if ($election->voting_start_at)
                                        {{ $election->voting_start_at->format('d M Y') }}
                                        @if ($election->voting_end_at)
                                            <span class="fc-sub">to {{ $election->voting_end_at->format('d M Y') }}</span>
                                        @endif
                                    @else
                                        <span class="fc-dim">Not scheduled</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="sliders" label="Manage election" :href="route('society.elections.show', $election->id)" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="clipboard2-check" title="No elections yet"
                :action-url="route('society.elections.create')" action-label="Create the first one" />
        @endif

        <x-fc.footer :paginator="$elections" />
    </div>
@stop
