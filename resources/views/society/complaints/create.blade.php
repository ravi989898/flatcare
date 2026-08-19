@extends('society.layout')

@section('title', 'Log Complaint')

@section('content')
    <h1 class="h3 mb-4">Log Complaint</h1>

    <form action="{{ route('society.complaints.store') }}" method="POST">
        @csrf
        <div class="card stat-card">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="flat_id" class="form-label">Flat</label>
                        <select name="flat_id" id="flat_id" class="form-select @error('flat_id') is-invalid @enderror">
                            <option value="">— Not flat-specific —</option>
                            @foreach ($flats as $flat)
                                <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>{{ $flat->display_label }}</option>
                            @endforeach
                        </select>
                        @error('flat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach (\App\Models\Tenant\Complaint::CATEGORIES as $category)
                                <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="e.g. Loud music late at night" required>
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="against" class="form-label">Against</label>
                        <input type="text" name="against" id="against" class="form-control @error('against') is-invalid @enderror" value="{{ old('against') }}" placeholder="e.g. Flat B-203, Security guard">
                        @error('against')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                            @foreach (\App\Models\Tenant\Complaint::PRIORITIES as $priority)
                                <option value="{{ $priority }}" {{ old('priority', 'medium') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="raised_by_name" class="form-label">Raised By <span class="text-danger">*</span></label>
                        <input type="text" name="raised_by_name" id="raised_by_name" class="form-control @error('raised_by_name') is-invalid @enderror" value="{{ old('raised_by_name') }}" required>
                        @error('raised_by_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="raised_by_phone" class="form-label">Contact Phone</label>
                        <input type="text" name="raised_by_phone" id="raised_by_phone" class="form-control @error('raised_by_phone') is-invalid @enderror" value="{{ old('raised_by_phone') }}">
                        @error('raised_by_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('society.complaints.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Log Complaint</button>
            </div>
        </div>
    </form>
@stop
