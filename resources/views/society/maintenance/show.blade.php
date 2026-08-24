@extends('society.layout')

@section('title', $maintenanceRequest->title)

@php
    $statusBadge = [
        'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'info',
        'closed' => 'secondary', 'cancelled' => 'dark',
    ];
    $priorityBadge = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
@endphp

@section('content_header')
    <a href="{{ route('society.maintenance.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Maintenance
    </a>
    <div class="d-flex align-items-center gap-2 mt-2">
        <h1 class="h3 mb-0">{{ $maintenanceRequest->title }}</h1>
        <span class="badge bg-{{ $statusBadge[$maintenanceRequest->status] }}">{{ ucfirst(str_replace('_', ' ', $maintenanceRequest->status)) }}</span>
        <span class="badge bg-{{ $priorityBadge[$maintenanceRequest->priority] }}">{{ ucfirst($maintenanceRequest->priority) }} priority</span>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <div class="card stat-card mb-3">
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Location</dt>
                        <dd class="col-sm-8">{{ $maintenanceRequest->flat?->display_label ?? $maintenanceRequest->block?->name ?? 'Not flat-specific' }}</dd>

                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $maintenanceRequest->category)) }}</dd>

                        <dt class="col-sm-4">Raised By</dt>
                        <dd class="col-sm-8">
                            {{ $maintenanceRequest->raised_by_name }}
                            @if ($maintenanceRequest->raised_by_phone)
                                &middot; {{ $maintenanceRequest->raised_by_phone }}
                            @endif
                        </dd>

                        <dt class="col-sm-4">Assigned To</dt>
                        <dd class="col-sm-8">{{ $maintenanceRequest->assignedTo?->name ?? '— Unassigned —' }}</dd>

                        <dt class="col-sm-4">Logged</dt>
                        <dd class="col-sm-8">{{ $maintenanceRequest->created_at->format('d M Y, h:i A') }}</dd>

                        @if ($maintenanceRequest->resolved_at)
                            <dt class="col-sm-4">Resolved</dt>
                            <dd class="col-sm-8">{{ $maintenanceRequest->resolved_at->format('d M Y, h:i A') }}</dd>
                        @endif
                    </dl>
                    <hr>
                    <div class="text-muted small mb-1">Description</div>
                    <p class="mb-0">{{ $maintenanceRequest->description }}</p>

                    @if ($maintenanceRequest->resolution_notes)
                        <hr>
                        <div class="text-muted small mb-1">Latest Notes</div>
                        <p class="mb-0">{{ $maintenanceRequest->resolution_notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card stat-card">
                <div class="card-header bg-white"><strong>History</strong></div>
                <div class="card-body p-0">
                    @if ($maintenanceRequest->history)
                        <ul class="list-group list-group-flush">
                            @foreach (array_reverse($maintenanceRequest->history) as $entry)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $entry['action'])) }}</span>
                                        <span class="text-muted small">{{ \Illuminate\Support\Carbon::parse($entry['at'])->diffForHumans() }}</span>
                                    </div>
                                    @if (!empty($entry['note']))
                                        <div class="text-muted small mt-1">{{ $entry['note'] }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted p-3 mb-0">No history yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Update Request</strong></div>
                <div class="card-body p-4">
                    <form action="{{ route('society.maintenance.update', $maintenanceRequest->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="custom-select">
                                @foreach (\App\Models\Tenant\MaintenanceRequest::STATUSES as $status)
                                    <option value="{{ $status }}" {{ $maintenanceRequest->status === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select name="priority" id="priority" class="custom-select">
                                @foreach (\App\Models\Tenant\MaintenanceRequest::PRIORITIES as $priority)
                                    <option value="{{ $priority }}" {{ $maintenanceRequest->priority === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="assigned_to_user_id" class="form-label">Assign To</label>
                            <select name="assigned_to_user_id" id="assigned_to_user_id" class="custom-select">
                                <option value="">— Unassigned —</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}" {{ $maintenanceRequest->assigned_to_user_id === $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">Note</label>
                            <textarea name="note" id="note" rows="3" class="form-control" placeholder="What changed / resolution details…"></textarea>
                        </div>

                        <button type="submit" class="btn btn-brand w-100"><i class="bi bi-check-lg"></i> Save Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
