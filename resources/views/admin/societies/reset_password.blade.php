@extends('adminlte::page')

@section('title', 'Reset Password')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Reset Admin Password</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Password not reset:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Admin Accounts</h3>
            <div class="card-tools">
                <small class="text-muted">Sets the password directly — the admin isn't emailed, so share the new password with them yourself.</small>
            </div>
        </div>
        <div class="card-body p-0">
            @if ($admins->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>New Password</th>
                            <th>Confirm Password</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $admin)
                            @php $formId = 'reset-password-form-' . $admin->id; @endphp
                            <tr>
                                <td class="align-middle">{{ $admin->name }}</td>
                                <td class="align-middle">
                                    <input type="text" class="form-control-plaintext" value="{{ $admin->email }}" readonly>
                                </td>
                                <td>
                                    <input type="password" class="form-control" name="password" form="{{ $formId }}" minlength="10" required>
                                </td>
                                <td>
                                    <input type="password" class="form-control" name="password_confirmation" form="{{ $formId }}" minlength="10" required>
                                </td>
                                <td class="align-middle">
                                    <form id="{{ $formId }}" action="{{ route('admin.societies.admins.reset_password', [$society->id, $admin->id]) }}" method="POST" novalidate>
                                        @csrf
                                    </form>
                                    <button type="submit" form="{{ $formId }}" class="btn btn-sm btn-primary" onclick="return confirm('Reset password for {{ $admin->name }}?')">
                                        <i class="fas fa-key"></i> Reset
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">
                    <p>No admin accounts found for this society.</p>
                </div>
            @endif
        </div>
    </div>
@stop
