@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Roles</h1>
        </div>
        <div class="col-sm-6 text-right">
            <form action="{{ route('admin.settings.roles.sync') }}" method="POST" class="d-inline" onsubmit="return confirm('Push the current role catalog into every provisioned society? Existing role assignments are kept.')">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sync"></i> Sync to Societies
                </button>
            </form>
            <a href="{{ route('admin.settings.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Role
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
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td><strong>{{ $role->display_name }}</strong> <code class="text-muted">{{ $role->name }}</code></td>
                            <td class="text-muted small">{{ $role->description }}</td>
                            <td>{{ $role->priority }}</td>
                            <td>
                                @if ($role->is_system_role)
                                    <span class="badge badge-info">System</span>
                                @else
                                    <span class="badge badge-secondary">Custom</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.settings.roles.edit', $role->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                @unless ($role->is_system_role)
                                    <form action="{{ route('admin.settings.roles.destroy', $role->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this role?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            This is the role template new societies are provisioned with. Editing a role here does not change
            an already-provisioned society until you click "Sync to Societies" — existing role assignments and
            permissions are never removed by a sync. To control which sidebar menus each role sees, go to
            <a href="{{ route('admin.settings.menu.edit') }}">Menu Settings</a>.
        </div>
    </div>
@stop
