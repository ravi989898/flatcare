@extends('society.layout')

@section('title', $complaint->subject)

@php
    $statusBadge = [
        'open' => 'danger', 'in_review' => 'warning', 'resolved' => 'info',
        'closed' => 'secondary', 'rejected' => 'dark',
    ];
    $priorityBadge = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
@endphp

@section('content_header')
    <a href="{{ route('society.complaints.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Complaints
    </a>
    <div class="d-flex align-items-center gap-2 mt-2">
        <h1 class="h3 mb-0">{{ $complaint->subject }}</h1>
        <span class="badge bg-{{ $statusBadge[$complaint->status] }}">{{ ucfirst(str_replace('_', ' ', $complaint->status)) }}</span>
        <span class="badge bg-{{ $priorityBadge[$complaint->priority] }}">{{ ucfirst($complaint->priority) }} priority</span>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <div class="card stat-card mb-3">
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Flat</dt>
                        <dd class="col-sm-8">{{ $complaint->flat?->display_label ?? 'Not flat-specific' }}</dd>

                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $complaint->category)) }}</dd>

                        @if ($complaint->against)
                            <dt class="col-sm-4">Against</dt>
                            <dd class="col-sm-8">{{ $complaint->against }}</dd>
                        @endif

                        <dt class="col-sm-4">Raised By</dt>
                        <dd class="col-sm-8">
                            {{ $complaint->raised_by_name }}
                            @if ($complaint->raised_by_phone)
                                &middot; {{ $complaint->raised_by_phone }}
                            @endif
                        </dd>

                        <dt class="col-sm-4">Assigned To</dt>
                        <dd class="col-sm-8">{{ $complaint->assignedTo?->name ?? '— Unassigned —' }}</dd>

                        <dt class="col-sm-4">Logged</dt>
                        <dd class="col-sm-8">{{ $complaint->created_at->format('d M Y, h:i A') }}</dd>

                        @if ($complaint->resolved_at)
                            <dt class="col-sm-4">Resolved</dt>
                            <dd class="col-sm-8">{{ $complaint->resolved_at->format('d M Y, h:i A') }}</dd>
                        @endif
                    </dl>
                    <hr>
                    <div class="text-muted small mb-1">Description</div>
                    <p class="mb-0">{{ $complaint->description }}</p>

                    @if ($complaint->resolution_notes)
                        <hr>
                        <div class="text-muted small mb-1">Latest Notes</div>
                        <p class="mb-0">{{ $complaint->resolution_notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card stat-card">
                <div class="card-header bg-white"><strong>History</strong></div>
                <div class="card-body p-0">
                    @if ($complaint->history)
                        <ul class="list-group list-group-flush">
                            @foreach (array_reverse($complaint->history) as $entry)
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
                <div class="card-header bg-white"><strong>Update Complaint</strong></div>
                <div class="card-body p-4">
                    <form action="{{ route('society.complaints.update', $complaint->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="custom-select">
                                @foreach (\App\Models\Tenant\Complaint::STATUSES as $status)
                                    <option value="{{ $status }}" {{ $complaint->status === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select name="priority" id="priority" class="custom-select">
                                @foreach (\App\Models\Tenant\Complaint::PRIORITIES as $priority)
                                    <option value="{{ $priority }}" {{ $complaint->priority === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="assigned_to_user_id" class="form-label">Assign To</label>
                            <select name="assigned_to_user_id" id="assigned_to_user_id" class="custom-select">
                                <option value="">— Unassigned —</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}" {{ $complaint->assigned_to_user_id === $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
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
