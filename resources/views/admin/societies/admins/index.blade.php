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
                                    @php $displayRole = $admin->roles->firstWhere('name', '!=', 'resident') ?? $admin->roles->first(); @endphp
                                    @if ($displayRole)
                                        <span class="badge badge-info">{{ $displayRole->display_name }}</span>
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
                                    <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#resetPasswordModal-{{ $admin->id }}">
                                        <i class="fas fa-key"></i> Forgot Password
                                    </button>
                                    @if ($admin->status === 'active')
                                        <form action="{{ route('admin.societies.admins.deactivate', [$society->id, $admin->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" data-confirm="Deactivate this admin?">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.societies.admins.activate', [$society->id, $admin->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" data-confirm="Activate this admin?">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                        </form>
                                    @endif
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

        @foreach ($admins as $admin)
            <div class="modal fade" id="resetPasswordModal-{{ $admin->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <form action="{{ route('admin.societies.admins.reset_password', [$society->id, $admin->id]) }}" method="POST" novalidate class="js-ajax-reset-password">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reset Password &ndash; {{ $admin->name }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="alert d-none" data-js-feedback></div>
                                <small class="text-muted d-block mb-3">Sets the password directly &mdash; the admin isn't emailed, so share the new password with them yourself.</small>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" class="form-control" name="password" minlength="10" required>
                                    <small class="form-text text-muted">Minimum 10 characters</small>
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input type="password" class="form-control" name="password_confirmation" minlength="10" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
@stop

@section('js')
    <script src="{{ asset('js/admin-reset-password-modal.js') }}"></script>
@stop
