@extends('society.layout')

@section('title', $announcement->title)

@php
    $categoryBadge = ['general' => 'secondary', 'maintenance' => 'info', 'event' => 'success', 'urgent' => 'danger', 'other' => 'dark'];
@endphp

@section('content')
    <div class="mb-4">
        <a href="{{ route('society.announcements.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Announcements
        </a>
    </div>

    <div class="card stat-card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        @if ($announcement->is_pinned)
                            <i class="bi bi-pin-angle-fill text-warning"></i>
                        @endif
                        <h1 class="h4 mb-0">{{ $announcement->title }}</h1>
                        <span class="badge bg-{{ $categoryBadge[$announcement->category] }}">{{ ucfirst($announcement->category) }}</span>
                    </div>
                    <p class="text-muted small mb-0">
                        {{ $announcement->postedBy?->name ?? 'Society Admin' }} &middot;
                        {{ $announcement->published_at?->format('d M Y, h:i A') ?? $announcement->created_at->format('d M Y, h:i A') }}
                        @if ($announcement->expires_at)
                            &middot; Expires {{ $announcement->expires_at->format('d M Y') }}
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('society.announcements.edit', $announcement->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @if ($announcement->status !== 'archived')
                        <form action="{{ route('society.announcements.destroy', $announcement->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this announcement?')">
                                <i class="bi bi-archive"></i> Archive
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <p class="mb-0" style="white-space: pre-line;">{{ $announcement->body }}</p>
        </div>
    </div>
@stop
