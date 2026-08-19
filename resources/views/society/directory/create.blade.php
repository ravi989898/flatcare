@extends('society.layout')

@section('title', 'Add Resident')

@section('content')
    <h1 class="h3 mb-4">Add Resident</h1>

    <form action="{{ route('society.directory.store') }}" method="POST">
        @csrf
        <div class="card stat-card">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="flat_id" class="form-label">Flat <span class="text-danger">*</span></label>
                        <select name="flat_id" id="flat_id" class="form-select @error('flat_id') is-invalid @enderror" required>
                            <option value="">— Select a flat —</option>
                            @foreach ($flats as $flat)
                                <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>{{ $flat->display_label }}</option>
                            @endforeach
                        </select>
                        @error('flat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="resident_type" class="form-label">Resident Type <span class="text-danger">*</span></label>
                        <select name="resident_type" id="resident_type" class="form-select @error('resident_type') is-invalid @enderror" required>
                            <option value="owner" {{ old('resident_type', 'owner') === 'owner' ? 'selected' : '' }}>Owner</option>
                            <option value="tenant" {{ old('resident_type') === 'tenant' ? 'selected' : '' }}>Tenant</option>
                            <option value="occupant" {{ old('resident_type') === 'occupant' ? 'selected' : '' }}>Occupant</option>
                        </select>
                        @error('resident_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_primary" value="0">
                            <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary" value="1" {{ old('is_primary') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_primary">Primary contact for this flat</label>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="bi bi-info-circle"></i>
                    A resident portal login isn't available yet — this just adds them to the directory so other modules (visitors, complaints, maintenance) can reference them.
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('society.directory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Add Resident</button>
            </div>
        </div>
    </form>
@stop
