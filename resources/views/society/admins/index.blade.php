@extends('society.layout')

@section('title', 'Admins')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Admins</h1>
            <p class="text-muted mb-0">Society admin accounts</p>
        </div>
        <a href="{{ route('society.admins.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Add Admin
        </a>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($admins->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($admins as $admin)
                                <tr>
                                    <td>{{ $admin->name }}</td>
                                    <td>{{ $admin->email }}</td>
                                    <td>{{ $admin->phone }}</td>
                                    <td>
                                        @if ($admin->roles->count() > 0)
                                            <span class="badge bg-info-subtle text-info-emphasis">{{ $admin->roles->first()->display_name }}</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">No Role</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($admin->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif ($admin->status === 'inactive')
                                            <span class="badge bg-warning text-dark">Inactive</span>
                                        @else
                                            <span class="badge bg-danger">{{ ucfirst($admin->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : 'Never' }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('society.admins.edit', $admin->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            @if ($admin->status === 'active')
                                                <form action="{{ route('society.admins.deactivate', $admin->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Deactivate this admin?')">Deactivate</button>
                                                </form>
                                            @else
                                                <form action="{{ route('society.admins.activate', $admin->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Activate this admin?')">Activate</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-person-badge fs-1 d-block mb-2"></i>
                    <p class="mb-2">No admin users found.</p>
                    <a href="{{ route('society.admins.create') }}" class="btn btn-brand btn-sm">Add the first admin</a>
                </div>
            @endif
        </div>
    </div>

    @if ($admins->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $admins->links() }}
        </div>
    @endif
@stop
