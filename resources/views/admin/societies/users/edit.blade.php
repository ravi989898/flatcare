@extends('adminlte::page')

@section('title', 'Edit Resident')

@section('content_header')
    <h1>{{ $society->name }} - Edit Resident</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $user->name }}</h3>
        </div>
        <form action="{{ route('admin.societies.users.update', [$society->id, $user->id]) }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="block_select">Block <span class="text-danger">*</span></label>
                        <select id="block_select" class="form-control">
                            <option value="">— Select a block —</option>
                            @foreach ($blocks as $block)
                                <option value="{{ $block->id }}" {{ old('block_id', $residency?->flat?->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="flat_id">Flat No. <span class="text-danger">*</span></label>
                        <select name="flat_id" id="flat_id" class="form-control @error('flat_id') is-invalid @enderror" data-filtered-by="block_select" required>
                            <option value="">— Select a block first —</option>
                            @foreach ($flats as $flat)
                                <option value="{{ $flat->id }}" data-block-id="{{ $flat->block_id }}" {{ old('flat_id', $residency?->flat_id) == $flat->id ? 'selected' : '' }}>
                                    {{ $flat->flat_number }}
                                </option>
                            @endforeach
                        </select>
                        @error('flat_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="resident_type">Resident Type <span class="text-danger">*</span></label>
                        <select name="resident_type" id="resident_type" class="form-control @error('resident_type') is-invalid @enderror" required>
                            <option value="owner" {{ old('resident_type', $residency?->resident_type ?? 'owner') === 'owner' ? 'selected' : '' }}>Owner</option>
                            <option value="tenant" {{ old('resident_type', $residency?->resident_type) === 'tenant' ? 'selected' : '' }}>Tenant</option>
                            <option value="occupant" {{ old('resident_type', $residency?->resident_type) === 'occupant' ? 'selected' : '' }}>Occupant</option>
                        </select>
                        @error('resident_type')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_primary" value="0">
                            <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary" value="1" {{ old('is_primary', $residency?->is_primary) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_primary">Primary contact for this flat</label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="phone">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20" required>
                        @error('phone')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', str_ends_with($user->email, '@placeholder.flatcare.local') ? '' : $user->email) }}">
                    <small class="form-text text-muted">Optional — the resident app logs in with the flat's mobile number, not email.</small>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ route('admin.societies.users.index', $society->id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
@stop
