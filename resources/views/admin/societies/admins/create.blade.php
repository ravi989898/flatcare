@extends('adminlte::page')

@section('title', 'Add Society Admin')

@section('content_header')
    <h1>{{ $society->name }} - Add Admin</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New Admin User</h3>
        </div>
        <form action="{{ route('admin.societies.admins.store', $society->id) }}" method="POST" novalidate>
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="user_id">Select User <span class="text-danger">*</span></label>
                    <select class="form-control @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                        <option value="">-- Select User --</option>
                        @foreach ($users as $user)
                            @php
                                $residency = $user->residencies->firstWhere('is_primary', true) ?? $user->residencies->first();
                                $flatNumber = $residency?->flat?->flat_number;
                            @endphp
                            <option value="{{ $user->id }}"
                                data-name="{{ $user->name }}"
                                data-email="{{ $user->email }}"
                                data-phone="{{ $user->phone }}"
                                {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $flatNumber ?? $user->phone }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">Only existing society users who aren't already admins are listed.</small>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" id="name" readonly>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" readonly>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="password">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Minimum 10 characters</small>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Role <span class="text-danger">*</span></label>
                    <select class="form-control @error('role') is-invalid @enderror" id="role" name="role" required>
                        <option value="">-- Select Role --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ route('admin.societies.admins.index', $society->id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Admin
                </button>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script src="{{ asset('js/admin-add-user-autofill.js') }}"></script>
@stop
