@extends('society.layout')

@section('title', 'Complaints')

@php
    $statusBadge = [
        'open' => 'danger', 'in_review' => 'warning', 'resolved' => 'info',
        'closed' => 'secondary', 'rejected' => 'dark',
    ];
    $priorityBadge = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Complaints</h1>
            <p class="text-muted mb-0">
                {{ $statusCounts->get('open', 0) + $statusCounts->get('in_review', 0) }} open &middot;
                {{ $statusCounts->sum() }} total
            </p>
        </div>
        <a href="{{ route('society.complaints.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Log Complaint
        </a>
    </div>

    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.complaints.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Subject, resident, or against">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\Complaint::STATUSES as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }} ({{ $statusCounts->get($status, 0) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\Complaint::PRIORITIES as $priority)
                            <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\Complaint::CATEGORIES as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
                    @if (request()->anyFilled(['search', 'status', 'priority', 'category']))
                        <a href="{{ route('society.complaints.index') }}" class="btn btn-link">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($complaints->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Flat</th>
                                <th>Category</th>
                                <th>Against</th>
                                <th>Raised By</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Logged</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($complaints as $complaint)
                                <tr>
                                    <td><a href="{{ route('society.complaints.show', $complaint->id) }}" class="text-decoration-none">{{ $complaint->subject }}</a></td>
                                    <td>{{ $complaint->flat?->display_label ?? '—' }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $complaint->category)) }}</td>
                                    <td>{{ $complaint->against ?: '—' }}</td>
                                    <td>{{ $complaint->raised_by_name }}</td>
                                    <td><span class="badge bg-{{ $priorityBadge[$complaint->priority] }}">{{ ucfirst($complaint->priority) }}</span></td>
                                    <td><span class="badge bg-{{ $statusBadge[$complaint->status] }}">{{ ucfirst(str_replace('_', ' ', $complaint->status)) }}</span></td>
                                    <td class="text-muted small">{{ $complaint->created_at->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('society.complaints.show', $complaint->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-megaphone fs-1 d-block mb-2"></i>
                    <p class="mb-2">No complaints found.</p>
                    <a href="{{ route('society.complaints.create') }}" class="btn btn-brand btn-sm">Log the first one</a>
                </div>
            @endif
        </div>
    </div>

    @if ($complaints->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $complaints->links() }}
        </div>
    @endif
@stop
