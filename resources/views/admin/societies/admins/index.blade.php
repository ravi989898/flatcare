@extends('adminlte::page')

@section('title', 'Society Admins')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Admins</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.admins.create', $society->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Admin
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Admin Users</h3>
        </div>
        <div class="card-body p-0">
            @if ($admins->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
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
                                        <span class="badge badge-info">{{ $admin->roles->first()->display_name }}</span>
                                    @else
                                        <span class="badge badge-secondary">No Role</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($admin->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif ($admin->status === 'inactive')
                                        <span class="badge badge-warning">Inactive</span>
                                    @else
                                        <span class="badge badge-danger">{{ ucfirst($admin->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : 'Never' }}</td>
                                <td>
                                    <a href="{{ route('admin.societies.admins.edit', [$society->id, $admin->id]) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    @if ($admin->status === 'active')
                                        <form action="{{ route('admin.societies.admins.deactivate', [$society->id, $admin->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this admin?')">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.societies.admins.destroy', [$society->id, $admin->id]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this admin?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">
                    <p>No admin users found for this society.</p>
                    <a href="{{ route('admin.societies.admins.create', $society->id) }}" class="btn btn-primary">
                        Create First Admin
                    </a>
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
