@extends('society.layout')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .small-box { overflow: hidden; min-height: 130px; }
    </style>
@endpush

@section('content_header')
    <h1 class="h3 mb-1">Welcome, {{ $user->name }}</h1>
    <p class="text-muted mb-0">{{ $society->name }} &middot; {{ $society->city }}, {{ $society->state }}</p>
@stop

@section('content')
    <div class="row mb-4">
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

    <div class="row">
        @foreach ([
            ['title' => 'Visitors', 'desc' => 'Log and track visitor check-ins and check-outs.', 'icon' => 'bi-person-badge', 'color' => 'bg-info', 'route' => 'society.visitors.index'],
            ['title' => 'Complaints', 'desc' => 'Log and resolve resident complaints.', 'icon' => 'bi-megaphone', 'color' => 'bg-danger', 'route' => 'society.complaints.index'],
            ['title' => 'Directory', 'desc' => 'Browse and search residents by name, flat or phone.', 'icon' => 'bi-people', 'color' => 'bg-success', 'route' => 'society.directory.index'],
            ['title' => 'Announcements', 'desc' => 'Post and manage society-wide notices.', 'icon' => 'bi-megaphone-fill', 'color' => 'bg-warning', 'route' => 'society.announcements.index'],
            ['title' => 'Events', 'desc' => 'Plan and manage society events and activities.', 'icon' => 'bi-calendar-event', 'color' => 'bg-indigo', 'route' => 'society.events.index'],
            ['title' => 'Elections', 'desc' => 'Run committee elections: nominations, voting, results.', 'icon' => 'bi-clipboard2-check', 'color' => 'bg-teal', 'route' => 'society.elections.index'],
            ['title' => 'Payments', 'desc' => 'Raise maintenance bills and record payments.', 'icon' => 'bi-cash-coin', 'color' => 'bg-purple', 'route' => 'society.payments.index'],
            ['title' => 'Extra Charges', 'desc' => 'Function usage, hall booking, renovation fund, transfer fee, etc.', 'icon' => 'bi-cash-stack', 'color' => 'bg-orange', 'route' => 'society.extra-charges.index'],
        ] as $module)
            <div class="col-lg-4 col-md-6 col-6">
                <a href="{{ route($module['route']) }}" class="small-box {{ $module['color'] }} text-white text-decoration-none">
                    <div class="inner">
                        <h5>{{ $module['title'] }}</h5>
                        <p>{{ $module['desc'] }}</p>
                    </div>
                    <div class="icon"><i class="bi {{ $module['icon'] }}"></i></div>
                </a>
            </div>
        @endforeach
    </div>
@stop
