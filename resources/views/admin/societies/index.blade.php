@extends('adminlte::page')

@section('title', 'Societies')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Societies</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Society
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

    <div class="card">
        <div class="card-header">
            <form action="{{ route('admin.societies.index') }}" method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Search name, email, city…" value="{{ request('search') }}">
                <select name="status" class="form-control mr-2">
                    <option value="">All Statuses</option>
                    @foreach (['active', 'inactive', 'expired', 'archived'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
                @if (request('search') || request('status'))
                    <a href="{{ route('admin.societies.index') }}" class="btn btn-link">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            @if ($societies->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Database</th>
                            <th>Access Period</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($societies as $society)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.societies.show', $society->id) }}">{{ $society->name }}</a>
                                    @if ($society->is_trial)
                                        <span class="badge badge-secondary">Trial</span>
                                    @endif
                                </td>
                                <td>{{ $society->city }}, {{ $society->state }}</td>
                                <td>
                                    @php
                                        $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'danger', 'archived' => 'dark'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$society->status] ?? 'secondary' }}">{{ ucfirst($society->status) }}</span>
                                </td>
                                <td>
                                    @php $dbStatus = $society->database->status ?? 'not created'; @endphp
                                    @php
                                        $dbColors = ['active' => 'success', 'creating' => 'info', 'created' => 'info', 'failed' => 'danger'];
                                    @endphp
                                    <span class="badge badge-{{ $dbColors[$dbStatus] ?? 'secondary' }}">{{ ucfirst($dbStatus) }}</span>
                                </td>
                                <td>{{ $society->start_date->format('d M Y') }} &ndash; {{ $society->end_date->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.societies.edit', $society->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="{{ route('admin.societies.admins.index', $society->id) }}" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-user-shield"></i> Admins
                                    </a>
                                    <form action="{{ route('admin.societies.destroy', $society->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Archive this society?')">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">
                    <p>No societies found.</p>
                    <a href="{{ route('admin.societies.create') }}" class="btn btn-primary">Create First Society</a>
                </div>
            @endif
        </div>
    </div>

    @if ($societies->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $societies->links() }}
        </div>
    @endif
@stop
