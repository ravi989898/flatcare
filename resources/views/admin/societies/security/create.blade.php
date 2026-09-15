@extends('adminlte::page')

@section('title', 'Add Security Guard')

@section('content_header')
    <h1>{{ $society->name }} - Add Security Guard</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Security Guard Details</h3>
        </div>
        <form action="{{ route('admin.societies.security.store', $society->id) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="10 digits" maxlength="10" data-validate="phone" required>
                    @error('phone')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="shift">Shift <span class="text-danger">*</span></label>
                    <select class="form-control @error('shift') is-invalid @enderror" id="shift" name="shift" required>
                        <option value="">-- Select Shift --</option>
                        <option value="day" {{ old('shift') === 'day' ? 'selected' : '' }}>Day</option>
                        <option value="night" {{ old('shift') === 'night' ? 'selected' : '' }}>Night</option>
                    </select>
                    @error('shift')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">This guard becomes on-duty for the selected shift, replacing whoever is currently on it.</small>
                </div>

                <div class="form-group">
                    <label for="aadhar_last4">Aadhar Card (Last 4 Digits)</label>
                    <input type="text" class="form-control @error('aadhar_last4') is-invalid @enderror" id="aadhar_last4" name="aadhar_last4" value="{{ old('aadhar_last4') }}" placeholder="e.g. 1234" maxlength="4">
                    @error('aadhar_last4')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">Optional.</small>
                </div>

                <div class="form-group">
                    <label for="photo">Photo</label>
                    <input type="file" class="form-control-file @error('photo') is-invalid @enderror" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp" data-validate="file" data-allowed-ext="jpg,jpeg,png,webp" data-max-size-kb="2048">
                    @error('photo')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">Shown to residents in the mobile app. Optional.</small>
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ route('admin.societies.security.index', $society->id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Security Guard
                </button>
            </div>
        </form>
    </div>
@stop
