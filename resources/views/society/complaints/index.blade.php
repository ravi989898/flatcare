@extends('society.layout')

@section('title', 'Complaints')

@php
    $statusTone = ['open' => 'warning', 'in_review' => 'info', 'resolved' => 'success', 'closed' => 'neutral', 'rejected' => 'danger'];
    $priorityTone = ['low' => 'neutral', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
    $openCount = $statusCounts->get('open', 0) + $statusCounts->get('in_review', 0);
    $statusTabs = ['' => ['All', $statusCounts->sum()]];
    foreach (\App\Models\Tenant\Complaint::STATUSES as $status) {
        $statusTabs[$status] = [ucfirst(str_replace('_', ' ', $status)), $statusCounts->get($status, 0)];
    }
@endphp

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Complaints</h1>
            <p class="text-muted mb-0">{{ $openCount }} open &middot; {{ $statusCounts->sum() }} total</p>
        </div>
        <a href="{{ route('society.complaints.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Log Complaint
        </a>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.tabs name="status" label="Complaint status" :tabs="$statusTabs" />

        <x-fc.toolbar placeholder="Search subject, resident, flat…" :keep="['status']">
            <x-fc.select name="priority" placeholder="Any priority"
                :options="collect(\App\Models\Tenant\Complaint::PRIORITIES)->mapWithKeys(fn ($p) => [$p => ucfirst($p)])" />
            <x-fc.select name="category" placeholder="All categories"
                :options="collect(\App\Models\Tenant\Complaint::CATEGORIES)->mapWithKeys(fn ($c) => [$c => ucfirst(str_replace('_', ' ', $c))])" />
        </x-fc.toolbar>

        @if ($complaints->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="subject">Complaint</x-fc.th>
                            <th>Flat</th>
                            <th>Against</th>
                            <x-fc.th sort="priority" first="desc">Priority</x-fc.th>
                            <x-fc.th sort="status">Status</x-fc.th>
                            <x-fc.th sort="logged" :default="true" first="desc">Logged</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($complaints as $complaint)
                            <tr>
                                <td>
                                    <a href="{{ route('society.complaints.show', $complaint->id) }}" class="fc-strong">{{ $complaint->subject }}</a>
                                    <span class="fc-sub">{{ ucfirst(str_replace('_', ' ', $complaint->category)) }} &middot; by {{ $complaint->raised_by_name }}</span>
                                </td>
                                <td class="fc-nowrap">
                                    @if ($complaint->flat)
                                        <span class="fc-strong">{{ $complaint->flat->flat_number }}</span>
                                        <span class="fc-sub">{{ $complaint->flat->block?->name }}</span>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td>{!! $complaint->against ? e($complaint->against) : '<span class="fc-dim">—</span>' !!}</td>
                                <td><x-fc.pill :status="$complaint->priority" :tone="$priorityTone[$complaint->priority] ?? 'neutral'" /></td>
                                <td><x-fc.pill :status="$complaint->status" :tone="$statusTone[$complaint->status] ?? 'neutral'" /></td>
                                <td class="fc-nowrap" title="{{ $complaint->created_at->format('d M Y, h:i A') }}">
                                    {{ $complaint->created_at->format('d M Y') }}
                                    <span class="fc-sub">{{ $complaint->created_at->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="eye" label="View complaint" :href="route('society.complaints.show', $complaint->id)" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="megaphone" title="No complaints" text="Complaints raised by residents appear here." :keep="['status']"
                :action-url="route('society.complaints.create')" action-label="Log a complaint" />
        @endif

        <x-fc.footer :paginator="$complaints" />
    </div>
@stop
