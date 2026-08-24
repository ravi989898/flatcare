@extends('society.layout')

@section('title', 'Announcements')

@php
    $categoryBadge = ['general' => 'secondary', 'maintenance' => 'info', 'event' => 'success', 'urgent' => 'danger', 'other' => 'dark'];
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Announcements</h1>
            <p class="text-muted mb-0">Society-wide notices board</p>
        </div>
        <a href="{{ route('society.announcements.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> New Announcement
        </a>
    </div>
@stop

@section('content')
    <div class="mb-3">
        <div class="btn-group">
            <a href="{{ route('society.announcements.index') }}" class="btn btn-sm {{ !$status ? 'btn-brand' : 'btn-outline-secondary' }}">Published</a>
            <a href="{{ route('society.announcements.index', ['status' => 'draft']) }}" class="btn btn-sm {{ $status === 'draft' ? 'btn-brand' : 'btn-outline-secondary' }}">Drafts</a>
            <a href="{{ route('society.announcements.index', ['status' => 'archived']) }}" class="btn btn-sm {{ $status === 'archived' ? 'btn-brand' : 'btn-outline-secondary' }}">Archived</a>
        </div>
    </div>

    @if ($announcements->count() > 0)
        @foreach ($announcements as $announcement)
            <div class="card stat-card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if ($announcement->is_pinned)
                                    <i class="bi bi-pin-angle-fill text-warning" title="Pinned"></i>
                                @endif
                                <a href="{{ route('society.announcements.show', $announcement->id) }}" class="h5 mb-0 text-decoration-none">{{ $announcement->title }}</a>
                                <span class="badge bg-{{ $categoryBadge[$announcement->category] }}">{{ ucfirst($announcement->category) }}</span>
                                @if ($announcement->isExpired())
                                    <span class="badge bg-secondary">Expired</span>
                                @endif
                            </div>
                            <p class="text-muted mb-1">{{ Str::limit($announcement->body, 180) }}</p>
                            <p class="text-muted small mb-0">
                                {{ $announcement->postedBy?->name ?? 'Society Admin' }} &middot;
                                {{ $announcement->published_at?->diffForHumans() ?? $announcement->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <a href="{{ route('society.announcements.show', $announcement->id) }}" class="btn btn-sm btn-outline-secondary">Read</a>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-center mt-3">
            {{ $announcements->links() }}
        </div>
    @else
        <div class="card stat-card">
            <div class="card-body p-5 text-center text-muted">
                <i class="bi bi-megaphone fs-1 d-block mb-2"></i>
                <p class="mb-2">No announcements yet.</p>
                <a href="{{ route('society.announcements.create') }}" class="btn btn-brand btn-sm">Post the first one</a>
            </div>
        </div>
    @endif
@stop
