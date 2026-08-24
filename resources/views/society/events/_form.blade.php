@php
    $old = fn ($field, $default = null) => old($field, $event->{$field} ?? $default);
    $oldDateTime = fn ($field) => old($field, $event?->{$field}?->format('Y-m-d\TH:i'));
@endphp

<div class="card stat-card">
    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-8">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ $old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category" id="category" class="custom-select @error('category') is-invalid @enderror" required>
                    @foreach (\App\Models\Tenant\Event::CATEGORIES as $category)
                        <option value="{{ $category }}" {{ $old('category', 'other') === $category ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror" required>{{ $old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="location" class="form-label">Location</label>
                <input type="text" name="location" id="location" class="form-control @error('location') is-invalid @enderror" value="{{ $old('location') }}" placeholder="e.g. Clubhouse, Garden area">
                @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="start_at" class="form-label">Starts <span class="text-danger">*</span></label>
                <input type="datetime-local" name="start_at" id="start_at" class="form-control @error('start_at') is-invalid @enderror" value="{{ $oldDateTime('start_at') }}" required>
                @error('start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="end_at" class="form-label">Ends</label>
                <input type="datetime-local" name="end_at" id="end_at" class="form-control @error('end_at') is-invalid @enderror" value="{{ $oldDateTime('end_at') }}">
                @error('end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
