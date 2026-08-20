@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Super Admin Dashboard</h1>
@stop

@section('content')
    {{-- Societies --}}
    <div class="row">
        @if (in_array('total_societies', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total_societies'] }}</h3>
                        <p>Total Societies</p>
                    </div>
                    <div class="icon"><i class="fas fa-building"></i></div>
                    <a href="{{ route('admin.societies.index') }}" class="small-box-footer">
                        View all <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        @endif

        @if (in_array('active_societies', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['active_societies'] }}</h3>
                        <p>Active Societies</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <a href="{{ route('admin.societies.index', ['status' => 'active']) }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        @endif

        @if (in_array('inactive_societies', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['inactive_societies'] }}</h3>
                        <p>Inactive Societies</p>
                    </div>
                    <div class="icon"><i class="fas fa-pause-circle"></i></div>
                    <a href="{{ route('admin.societies.index', ['status' => 'inactive']) }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        @endif

        @if (in_array('expired_societies', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats['expired_societies'] }}</h3>
                        <p>Expired Societies</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                    <a href="{{ route('admin.societies.index', ['status' => 'expired']) }}" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Society structure & people, aggregated across every tenant database --}}
    <div class="row">
        @if (in_array('total_blocks', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $stats['total_blocks'] }}</h3>
                        <p>Total Blocks</p>
                    </div>
                    <div class="icon"><i class="fas fa-layer-group"></i></div>
                </div>
            </div>
        @endif

        @if (in_array('total_flats', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $stats['total_flats'] }}</h3>
                        <p>Total Flats</p>
                    </div>
                    <div class="icon"><i class="fas fa-door-closed"></i></div>
                </div>
            </div>
        @endif

        @if (in_array('total_registered_users', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['total_registered_users'] }}</h3>
                        <p>Total Registered Users</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
        @endif

        @if (in_array('total_society_admins', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['total_society_admins'] }}</h3>
                        <p>Total Society Admins</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-shield"></i></div>
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        @if (in_array('total_committee_members', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['total_committee_members'] }}</h3>
                        <p>Total Committee Members</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-tie"></i></div>
                </div>
            </div>
        @endif

        @if (in_array('total_security_users', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['total_security_users'] }}</h3>
                        <p>Total Security Users</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-shield"></i></div>
                </div>
            </div>
        @endif

        @if (in_array('active_maintenance_configs', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $stats['active_maintenance_configs'] }}</h3>
                        <p>Active Maintenance Configs</p>
                        <small class="text-white-50">Coming soon</small>
                    </div>
                    <div class="icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>
        @endif

        @if (in_array('pending_society_setup', $visibleWidgets))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['pending_society_setup'] }}</h3>
                        <p>Pending Society Setup</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
        @endif
    </div>

    {{-- Quick navigation --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Quick Navigation</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-6 mb-2">
                    <a href="{{ route('admin.societies.index') }}" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-building mr-1"></i> Societies
                    </a>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <a href="{{ route('admin.societies.create') }}" class="btn btn-outline-success btn-block">
                        <i class="fas fa-plus-circle mr-1"></i> Add Society
                    </a>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <a href="{{ route('admin.audit_logs.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-history mr-1"></i> Audit Logs
                    </a>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <a href="{{ route('admin.settings.branding.edit') }}" class="btn btn-outline-dark btn-block">
                        <i class="fas fa-image mr-1"></i> Branding
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue + Society Status, side by side --}}
    <div class="row">
        @if (in_array('revenue_chart', $visibleWidgets))
            <div class="col-lg-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Revenue — Last 6 Months</h3>
                        <p class="text-muted small mb-0">Maintenance-fee payments collected, summed across every society.</p>
                    </div>
                    <div class="card-body">
                        @if (collect($revenue)->sum('total') > 0)
                            <canvas id="revenueChart" height="200"></canvas>
                            {{-- Data island, not an inline <script> — see public/js/admin-dashboard-charts.js --}}
                            <script type="application/json" id="revenue-chart-data">@json($revenue)</script>
                        @else
                            <p class="text-center text-muted py-4 mb-0">No payments recorded yet across any society.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if (in_array('society_status_chart', $visibleWidgets))
            <div class="col-lg-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Societies by Status</h3>
                    </div>
                    <div class="card-body">
                        @if ($stats['total_societies'] > 0)
                            <canvas id="societyStatusChart" height="200"></canvas>
                            <script type="application/json" id="society-status-chart-data">@json(['active' => $stats['active_societies'], 'inactive' => $stats['inactive_societies'], 'expired' => $stats['expired_societies']])</script>
                        @else
                            <p class="text-center text-muted py-4 mb-0">No societies registered yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@stop

@if ((in_array('revenue_chart', $visibleWidgets) && collect($revenue)->sum('total') > 0)
        || (in_array('society_status_chart', $visibleWidgets) && $stats['total_societies'] > 0))
    {{-- External file, not inline JS — the CSP (script-src 'self' …, no
         'unsafe-inline') blocks inline <script> blocks just like it blocks
         inline onclick handlers. See public/js/admin-dashboard-charts.js. --}}
    @push('js')
        <script src="{{ asset('js/admin-dashboard-charts.js') }}"></script>
    @endpush
@endif
