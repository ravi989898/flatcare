@extends('society.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Welcome, {{ $user->name }}</h1>
        <p class="text-muted mb-0">{{ $society->name }} &middot; {{ $society->city }}, {{ $society->state }}</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="text-muted small">Your roles</div>
                        <div class="fw-semibold">
                            {{ $roles->count() > 0 ? $roles->implode(', ') : 'No role assigned' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="text-muted small">Permissions granted</div>
                        <div class="fw-semibold">{{ $permissionCount }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="text-muted small">Access valid until</div>
                        <div class="fw-semibold">{{ $society->end_date->format('d M Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-tools text-muted"></i> Maintenance</h5>
                        <a href="{{ route('society.maintenance.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Track and resolve maintenance requests for your society.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-person-badge text-muted"></i> Visitors</h5>
                        <a href="{{ route('society.visitors.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Log and track visitor check-ins and check-outs.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-megaphone text-muted"></i> Complaints</h5>
                        <a href="{{ route('society.complaints.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Log and resolve resident complaints.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-people text-muted"></i> Directory</h5>
                        <a href="{{ route('society.directory.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Browse and search residents by name, flat or phone.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-megaphone-fill text-muted"></i> Announcements</h5>
                        <a href="{{ route('society.announcements.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Post and manage society-wide notices.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-calendar-event text-muted"></i> Events</h5>
                        <a href="{{ route('society.events.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Plan and manage society events and activities.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-clipboard2-check text-muted"></i> Elections</h5>
                        <a href="{{ route('society.elections.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Run committee elections: nominations, voting, results.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="mb-0"><i class="bi bi-cash-coin text-muted"></i> Payments</h5>
                        <a href="{{ route('society.payments.index') }}" class="btn btn-brand btn-sm">
                            Open <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">Raise maintenance bills and record payments.</p>
                </div>
            </div>
        </div>
    </div>
@stop
