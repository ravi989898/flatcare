@extends('adminlte::page')

@section('title', 'Society Admins')

@section('content_header')
    <div class="fc-page-head">
        <h1 class="mb-0">{{ $society->name }} - Admins</h1>
        <a href="{{ route('admin.societies.admins.create', $society->id) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Admin
        </a>
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

    <div class="fc-card">
        <x-fc.toolbar placeholder="Search name, email or phone…">
            <x-fc.select name="status" placeholder="Any status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'blocked' => 'Blocked']" />
        </x-fc.toolbar>

        @if ($admins->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="name" :default="true" default-dir="asc">Admin</x-fc.th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <x-fc.th sort="last_login" first="desc">Last Login</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $admin)
                            @php $displayRole = $admin->roles->firstWhere('name', '!=', 'resident') ?? $admin->roles->first(); @endphp
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$admin->name" :round="true" />
                                        <div>
                                            <span class="fc-strong">{{ $admin->name }}</span>
                                            <span class="fc-sub">{{ $admin->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="fc-nowrap">{!! $admin->phone ? e($admin->phone) : '<span class="fc-dim">—</span>' !!}</td>
                                <td>
                                    @if ($displayRole)
                                        <x-fc.pill tone="purple" :dot="false" :label="$displayRole->display_name" />
                                    @else
                                        <x-fc.pill tone="neutral" :dot="false" label="No role" />
                                    @endif
                                </td>
                                <td><x-fc.pill :status="$admin->status" /></td>
                                <td class="fc-nowrap" @if ($admin->last_login_at) title="{{ $admin->last_login_at->format('d M Y, h:i A') }}" @endif>
                                    {!! $admin->last_login_at ? e($admin->last_login_at->diffForHumans()) : '<span class="fc-dim">Never</span>' !!}
                                </td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="pencil" label="Edit admin" :href="route('admin.societies.admins.edit', [$society->id, $admin->id])" />
                                        <x-fc.action icon="key" label="Reset password" data-toggle="modal" data-target="#resetPasswordModal-{{ $admin->id }}" />
                                        @if ($admin->status === 'active')
                                            <x-fc.post-action :action="route('admin.societies.admins.deactivate', [$society->id, $admin->id])"
                                                icon="pause-circle" label="Deactivate" tone="warning" confirm="Deactivate this admin?" />
                                        @else
                                            <x-fc.post-action :action="route('admin.societies.admins.activate', [$society->id, $admin->id])"
                                                icon="play-circle" label="Activate" confirm="Activate this admin?" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="person-badge" title="No admin users for this society yet">
                <a href="{{ route('admin.societies.admins.create', $society->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create First Admin</a>
            </x-fc.empty>
        @endif

        <x-fc.footer :paginator="$admins" />
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
@stop

@section('js')
    <script src="{{ asset('js/admin-reset-password-modal.js') }}"></script>
@stop
