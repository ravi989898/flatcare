@extends('society.layout')

@section('title', 'Polls & Surveys')

@php
    $statusTone = ['draft' => 'neutral', 'open' => 'success', 'closed' => 'neutral'];
@endphp

@section('content')
    <div class="fc-page-head mb-4">
        <div>
            <h1 class="h3 mb-1">Polls & Surveys</h1>
            <p class="text-muted mb-0">Ask residents a question and see live results.</p>
        </div>
        <a href="{{ route('society.polls.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> New Poll
        </a>
    </div>

    <div class="fc-card">
        <x-fc.toolbar placeholder="Search question…">
            <x-fc.select name="status" placeholder="Any status" :options="['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed']" />
        </x-fc.toolbar>

        @if ($polls->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="question">Question</x-fc.th>
                            <x-fc.th sort="status">Status</x-fc.th>
                            <th class="num">Options</th>
                            <th class="num">Votes</th>
                            <x-fc.th sort="created" :default="true" first="desc">Created</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($polls as $poll)
                            <tr>
                                <td><a href="{{ route('society.polls.show', $poll->id) }}" class="fc-strong">{{ $poll->question }}</a></td>
                                <td><x-fc.pill :status="$poll->status" :tone="$statusTone[$poll->status] ?? 'neutral'" /></td>
                                <td class="num">{{ $poll->options->count() }}</td>
                                <td class="num fc-strong">{{ $poll->options->sum('votes_count') }}</td>
                                <td class="fc-nowrap">{{ $poll->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="bar-chart" label="View results" :href="route('society.polls.show', $poll->id)" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="bar-chart-steps" title="No polls yet"
                :action-url="route('society.polls.create')" action-label="Create the first poll" />
        @endif

        <x-fc.footer :paginator="$polls" />
    </div>
@stop
