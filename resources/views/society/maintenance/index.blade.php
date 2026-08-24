@extends('society.layout')

@section('title', 'Maintenance')

@php
    $statusBadge = [
        'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'info',
        'closed' => 'secondary', 'cancelled' => 'dark',
    ];
    $priorityBadge = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Maintenance Requests</h1>
            <p class="text-muted mb-0">
                {{ $statusCounts->get('open', 0) + $statusCounts->get('in_progress', 0) }} open &middot;
                {{ $statusCounts->sum() }} total
            </p>
        </div>
        <a href="{{ route('society.maintenance.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Log Request
        </a>
    </div>
@stop

@section('content')
    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.maintenance.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Title or resident name">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="custom-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\MaintenanceRequest::STATUSES as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }} ({{ $statusCounts->get($status, 0) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Priority</label>
                    <select name="priority" class="custom-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\MaintenanceRequest::PRIORITIES as $priority)
                            <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Category</label>
                    <select name="category" class="custom-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\MaintenanceRequest::CATEGORIES as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
                    @if (request()->anyFilled(['search', 'status', 'priority', 'category']))
                        <a href="{{ route('society.maintenance.index') }}" class="btn btn-link">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($requests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Raised By</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Logged</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $req)
                                <tr>
                                    <td><a href="{{ route('society.maintenance.show', $req->id) }}" class="text-decoration-none">{{ $req->title }}</a></td>
                                    <td>{{ $req->flat?->display_label ?? $req->block?->name ?? '—' }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $req->category)) }}</td>
                                    <td>{{ $req->raised_by_name }}</td>
                                    <td><span class="badge bg-{{ $priorityBadge[$req->priority] }}">{{ ucfirst($req->priority) }}</span></td>
                                    <td><span class="badge bg-{{ $statusBadge[$req->status] }}">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span></td>
                                    <td class="text-muted small">{{ $req->created_at->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('society.maintenance.show', $req->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-tools fs-1 d-block mb-2"></i>
                    <p class="mb-2">No maintenance requests found.</p>
                    <a href="{{ route('society.maintenance.create') }}" class="btn btn-brand btn-sm">Log the first one</a>
                </div>
            @endif
        </div>
    </div>

    @if ($requests->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $requests->links() }}
        </div>
    @endif
@stop
