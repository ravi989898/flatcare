@extends('society.layout')

@section('title', 'Add Security Guard')

@section('content_header')
    <h1 class="h3 mb-0">Add Security Guard</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>Security Guard Details</strong></div>
        <form action="{{ route('society.security.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="10 digits" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Shift <span class="text-danger">*</span></label>
                    <select class="form-select @error('shift') is-invalid @enderror" name="shift" required>
                        <option value="">-- Select Shift --</option>
                        <option value="day" {{ old('shift') === 'day' ? 'selected' : '' }}>Day</option>
                        <option value="night" {{ old('shift') === 'night' ? 'selected' : '' }}>Night</option>
                    </select>
                    @error('shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">This guard becomes on-duty for the selected shift, replacing whoever is currently on it.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Aadhar Card (Last 4 Digits)</label>
                    <input type="text" class="form-control @error('aadhar_last4') is-invalid @enderror" name="aadhar_last4" value="{{ old('aadhar_last4') }}" placeholder="e.g. 1234" maxlength="4">
                    @error('aadhar_last4')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Optional.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Photo</label>
                    <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept="image/*">
                    @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Shown to residents in the mobile app. Optional.</small>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('society.security.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Add Security Guard</button>
            </div>
        </form>
    </div>
@stop
