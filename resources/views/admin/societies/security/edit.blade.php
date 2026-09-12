@extends('adminlte::page')

@section('title', 'Edit Security Guard')

@section('content_header')
    <h1>{{ $society->name }} - Edit Security Guard</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Security Guard Details</h3>
        </div>
        <form action="{{ route('admin.societies.security.update', [$society->id, $guard->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                @if ($guard->photo_path)
                    <div class="form-group">
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($guard->photo_path) }}" alt="{{ $guard->name }}" style="width:80px;height:80px;object-fit:cover;border-radius:50%;">
                    </div>
                @endif

                <div class="form-group">
                    <label for="name">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $guard->name) }}" required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $guard->phone) }}" placeholder="10 digits" required>
                    @error('phone')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="shift">Shift <span class="text-danger">*</span></label>
                    <select class="form-control @error('shift') is-invalid @enderror" id="shift" name="shift" required>
                        <option value="day" {{ old('shift', $guard->shift) === 'day' ? 'selected' : '' }}>Day</option>
                        <option value="night" {{ old('shift', $guard->shift) === 'night' ? 'selected' : '' }}>Night</option>
                    </select>
                    @error('shift')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    @if ($guard->status === 'active')
                        <small class="form-text text-muted">Changing this while the guard is Active moves them onto the new shift immediately, replacing whoever is currently on it.</small>
                    @endif
                </div>

                <div class="form-group">
                    <label for="aadhar_last4">Aadhar Card (Last 4 Digits)</label>
                    <input type="text" class="form-control @error('aadhar_last4') is-invalid @enderror" id="aadhar_last4" name="aadhar_last4" value="{{ old('aadhar_last4', $guard->aadhar_last4) }}" placeholder="e.g. 1234" maxlength="4">
                    @error('aadhar_last4')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">Optional.</small>
                </div>

                <div class="form-group">
                    <label for="photo">Replace Photo</label>
                    <input type="file" class="form-control-file @error('photo') is-invalid @enderror" id="photo" name="photo" accept="image/*">
                    @error('photo')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ route('admin.societies.security.index', $society->id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
@stop
