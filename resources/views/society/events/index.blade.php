@extends('society.layout')

@section('title', 'Events')

@php
    $categoryBadge = ['cultural' => 'info', 'sports' => 'success', 'meeting' => 'secondary', 'festival' => 'warning', 'other' => 'dark'];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Events</h1>
            <p class="text-muted mb-0">Society events and activities</p>
        </div>
        <a href="{{ route('society.events.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> New Event
        </a>
    </div>

    <div class="mb-3">
        <div class="btn-group">
            <a href="{{ route('society.events.index') }}" class="btn btn-sm {{ $tab === 'upcoming' ? 'btn-brand' : 'btn-outline-secondary' }}">Upcoming</a>
            <a href="{{ route('society.events.index', ['tab' => 'past']) }}" class="btn btn-sm {{ $tab === 'past' ? 'btn-brand' : 'btn-outline-secondary' }}">Past</a>
            <a href="{{ route('society.events.index', ['tab' => 'cancelled']) }}" class="btn btn-sm {{ $tab === 'cancelled' ? 'btn-brand' : 'btn-outline-secondary' }}">Cancelled</a>
        </div>
    </div>

    @if ($events->count() > 0)
        @foreach ($events as $event)
            <div class="card stat-card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex gap-3">
                            <div class="text-center" style="width: 56px;">
                                <div class="fw-bold text-brand" style="font-size:1.2rem;">{{ $event->start_at->format('d') }}</div>
                                <div class="text-muted small text-uppercase">{{ $event->start_at->format('M') }}</div>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <a href="{{ route('society.events.show', $event->id) }}" class="h5 mb-0 text-decoration-none">{{ $event->title }}</a>
                                    <span class="badge bg-{{ $categoryBadge[$event->category] }}">{{ ucfirst($event->category) }}</span>
                                    @if ($event->isCancelled())
                                        <span class="badge bg-danger">Cancelled</span>
                                    @endif
                                </div>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-clock"></i> {{ $event->start_at->format('d M Y, h:i A') }}
                                    @if ($event->location)
                                        &middot; <i class="bi bi-geo-alt"></i> {{ $event->location }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('society.events.show', $event->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-center mt-3">
            {{ $events->links() }}
        </div>
    @else
        <div class="card stat-card">
            <div class="card-body p-5 text-center text-muted">
                <i class="bi bi-calendar-event fs-1 d-block mb-2"></i>
                <p class="mb-2">No {{ $tab }} events.</p>
                <a href="{{ route('society.events.create') }}" class="btn btn-brand btn-sm">Create an event</a>
            </div>
        </div>
    @endif
@stop
