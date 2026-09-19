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
                                        @php $displayRole = $admin->roles->firstWhere('name', '!=', 'resident') ?? $admin->roles->first(); @endphp
                                        @if ($displayRole)
                                            <span class="badge bg-info-subtle text-info-emphasis">{{ $displayRole->display_name }}</span>
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
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#resetPasswordModal-{{ $admin->id }}">
                                                Forgot Password
                                            </button>
                                            @if ($admin->status === 'active')
                                                <form action="{{ route('society.admins.deactivate', $admin->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="Deactivate this admin?">Deactivate</button>
                                                </form>
                                            @else
                                                <form action="{{ route('society.admins.activate', $admin->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" data-confirm="Activate this admin?">Activate</button>
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

        @foreach ($admins as $admin)
            <div class="modal fade" id="resetPasswordModal-{{ $admin->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <form action="{{ route('society.admins.reset_password', $admin->id) }}" method="POST" novalidate class="js-ajax-reset-password">
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
                                <button type="submit" class="btn btn-brand">Reset Password</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
@stop

@push('js')
    <script src="{{ asset('js/admin-reset-password-modal.js') }}"></script>
@endpush
