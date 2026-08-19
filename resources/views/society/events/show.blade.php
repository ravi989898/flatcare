@extends('society.layout')

@section('title', $event->title)

@php
    $categoryBadge = ['cultural' => 'info', 'sports' => 'success', 'meeting' => 'secondary', 'festival' => 'warning', 'other' => 'dark'];
@endphp

@section('content')
    <div class="mb-4">
        <a href="{{ route('society.events.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Events
        </a>
    </div>

    <div class="card stat-card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h4 mb-0">{{ $event->title }}</h1>
                        <span class="badge bg-{{ $categoryBadge[$event->category] }}">{{ ucfirst($event->category) }}</span>
                        @if ($event->isCancelled())
                            <span class="badge bg-danger">Cancelled</span>
                        @elseif ($event->isPast())
                            <span class="badge bg-secondary">Past</span>
                        @endif
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-clock"></i> {{ $event->start_at->format('d M Y, h:i A') }}
                        @if ($event->end_at) &ndash; {{ $event->end_at->format('h:i A') }} @endif
                        @if ($event->location)
                            &middot; <i class="bi bi-geo-alt"></i> {{ $event->location }}
                        @endif
                    </p>
                    <p class="text-muted small mb-0">Posted by {{ $event->postedBy?->name ?? 'Society Admin' }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('society.events.edit', $event->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @if (!$event->isCancelled())
                        <form action="{{ route('society.events.destroy', $event->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this event?')">
                                <i class="bi bi-x-circle"></i> Cancel Event
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <p class="mb-0" style="white-space: pre-line;">{{ $event->description }}</p>
        </div>
    </div>
@stop
