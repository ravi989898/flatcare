@extends('society.layout')

@section('title', 'Add Admin')

@section('content_header')
    <h1 class="h3 mb-0">Add Admin</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>New Admin User</strong></div>
        <form action="{{ route('society.admins.store') }}" method="POST" novalidate>
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Block <span class="text-danger">*</span></label>
                        <select class="form-control" id="block_select">
                            <option value="">-- Select Block --</option>
                            @foreach ($blocks as $block)
                                <option value="{{ $block->id }}">{{ $block->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Flat <span class="text-danger">*</span></label>
                        <select class="form-control @error('user_id') is-invalid @enderror" id="flat_select" data-filtered-by="block_select" required>
                            <option value="">-- Select a block first --</option>
                            @foreach ($flats as $flat)
                                @php $resident = $flat->residents->first()?->user; @endphp
                                <option value="{{ $flat->id }}"
                                    data-block-id="{{ $flat->block_id }}"
                                    data-user-id="{{ $resident?->id }}"
                                    data-name="{{ $resident?->name }}"
                                    data-email="{{ $resident?->email }}"
                                    data-phone="{{ $resident?->phone }}"
                                    {{ !$resident ? 'disabled' : '' }}
                                    {{ old('user_id') && $resident && old('user_id') == $resident->id ? 'selected' : '' }}>
                                    {{ $flat->flat_number }}{{ $resident ? " ({$resident->name})" : ' (no resident yet)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <input type="hidden" id="user_id" name="user_id" value="{{ old('user_id') }}">

                <div class="mb-3">
                    <label class="font-weight-bold mb-1 d-block">Full Name</label>
                    <input type="text" class="form-control" id="name" readonly>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Email</label>
                        <input type="email" class="form-control" id="email" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Phone</label>
                        <input type="tel" class="form-control" id="phone" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Minimum 10 characters</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="font-weight-bold mb-1 d-block">Role <span class="text-danger">*</span></label>
                    <select class="form-control @error('role') is-invalid @enderror" name="role" required>
                        <option value="">-- Select Role --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card-footer bg-white">
                <a href="{{ route('society.admins.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Create Admin</button>
            </div>
        </form>
    </div>
@stop

@push('js')
    <script src="{{ asset('js/admin-add-user-autofill.js') }}"></script>
@endpush
