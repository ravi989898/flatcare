@extends('society.layout')

@section('title', 'Edit Security Guard')

@section('content_header')
    <h1 class="h3 mb-0">Edit Security Guard</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>{{ $guard->name }}</strong></div>
        <form action="{{ route('society.security.update', $guard->id) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')
            <div class="card-body">
                @if ($guard->photo_path)
                    <div class="mb-3">
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($guard->photo_path) }}" alt="{{ $guard->name }}" style="width:80px;height:80px;object-fit:cover;border-radius:50%;">
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $guard->name) }}" maxlength="255" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $guard->phone) }}" placeholder="10 digits" maxlength="10" data-validate="phone" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Shift <span class="text-danger">*</span></label>
                    <select class="form-select @error('shift') is-invalid @enderror" name="shift" required>
                        <option value="day" {{ old('shift', $guard->shift) === 'day' ? 'selected' : '' }}>Day</option>
                        <option value="night" {{ old('shift', $guard->shift) === 'night' ? 'selected' : '' }}>Night</option>
                    </select>
                    @error('shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if ($guard->status === 'active')
                        <small class="form-text text-muted">Changing this while the guard is Active moves them onto the new shift immediately, replacing whoever is currently on it.</small>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Aadhar Card (Last 4 Digits)</label>
                    <input type="text" class="form-control @error('aadhar_last4') is-invalid @enderror" name="aadhar_last4" value="{{ old('aadhar_last4', $guard->aadhar_last4) }}" placeholder="e.g. 1234" maxlength="4">
                    @error('aadhar_last4')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Optional.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Replace Photo</label>
                    <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept=".jpg,.jpeg,.png,.webp" data-validate="file" data-allowed-ext="jpg,jpeg,png,webp" data-max-size-kb="2048">
                    @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('society.security.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Save Changes</button>
            </div>
        </form>
    </div>
@stop
