@extends('adminlte::page')

@section('title', $society->name)

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }}</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.admins.index', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-user-shield"></i> Admins
            </a>
            <a href="{{ route('admin.societies.edit', $society->id) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Profile</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            @php $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'danger', 'archived' => 'dark']; @endphp
                            <span class="badge badge-{{ $statusColors[$society->status] ?? 'secondary' }}">{{ ucfirst($society->status) }}</span>
                            @if ($society->is_trial)<span class="badge badge-info">Trial</span>@endif
                            @if ($society->payment_verified)<span class="badge badge-success">Payment Verified</span>@endif
                        </dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $society->email }}</dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $society->phone }}{{ $society->alternate_phone ? ' / ' . $society->alternate_phone : '' }}</dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $society->address }}, {{ $society->city }}, {{ $society->state }} {{ $society->postal_code }}, {{ $society->country }}</dd>

                        <dt class="col-sm-4">Registration #</dt>
                        <dd class="col-sm-8">{{ $society->registration_number ?: '—' }}</dd>

                        <dt class="col-sm-4">Flats / Blocks</dt>
                        <dd class="col-sm-8">{{ $society->total_flats ?? '—' }} / {{ $society->total_blocks ?? '—' }}</dd>

                        <dt class="col-sm-4">Access Period</dt>
                        <dd class="col-sm-8">
                            {{ $society->start_date->format('d M Y') }} &ndash; {{ $society->end_date->format('d M Y') }}
                            @if ($society->is_active)
                                <span class="badge badge-success">{{ $society->days_remaining }} days left</span>
                            @elseif ($society->is_expired)
                                <span class="badge badge-danger">Expired</span>
                            @endif
                        </dd>

                        @if ($society->admin_name || $society->admin_email || $society->admin_phone)
                            <dt class="col-sm-4">Primary Contact</dt>
                            <dd class="col-sm-8">
                                {{ $society->admin_name }}
                                @if ($society->admin_email)<br>{{ $society->admin_email }}@endif
                                @if ($society->admin_phone)<br>{{ $society->admin_phone }}@endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Database</h3></div>
                <div class="card-body">
                    @php $db = $society->database; @endphp
                    @if ($db)
                        @php $dbColors = ['active' => 'success', 'creating' => 'info', 'created' => 'info', 'failed' => 'danger']; @endphp
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8"><span class="badge badge-{{ $dbColors[$db->status] ?? 'secondary' }}">{{ ucfirst($db->status) }}</span></dd>

                            <dt class="col-sm-4">Database</dt>
                            <dd class="col-sm-8"><code>{{ $db->db_name }}</code></dd>

                            <dt class="col-sm-4">Last Migrated</dt>
                            <dd class="col-sm-8">{{ $db->last_migrated_at ? $db->last_migrated_at->diffForHumans() : 'Never' }}</dd>

                            @if ($db->error_message)
                                <dt class="col-sm-4">Last Error</dt>
                                <dd class="col-sm-8 text-danger">{{ $db->error_message }}</dd>
                            @endif
                        </dl>
                        @if ($db->status !== 'active')
                            <form action="{{ route('admin.societies.retry_provisioning', $society->id) }}" method="POST" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fas fa-redo"></i> Retry Provisioning
                                </button>
                            </form>
                        @endif
                    @else
                        <p class="text-muted">No database has been provisioned for this society yet.</p>
                        <form action="{{ route('admin.societies.retry_provisioning', $society->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-database"></i> Provision Database
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Modules</h3></div>
        <div class="card-body">
            <div class="row">
                @foreach ($modules as $module)
                    @php $enabled = $enabledModuleIds->contains($module->id); @endphp
                    <div class="col-md-3 mb-3">
                        <div class="card {{ $enabled ? 'border-success' : 'border-secondary' }}">
                            <div class="card-body p-3">
                                <h6 class="mb-1">
                                    {{ $module->display_name }}
                                    @if ($module->is_core)<span class="badge badge-secondary">Core</span>@endif
                                </h6>
                                <p class="text-muted small mb-2">{{ $module->description }}</p>
                                <form action="{{ route('admin.societies.modules.toggle', [$society->id, $module->id]) }}" method="POST">
                                    @csrf
                                    @if ($module->is_core)
                                        <span class="badge badge-success">Always Enabled</span>
                                    @else
                                        <button type="submit" class="btn btn-sm {{ $enabled ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@stop
