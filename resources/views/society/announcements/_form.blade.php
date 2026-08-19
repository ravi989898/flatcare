@php
    $old = fn ($field, $default = null) => old($field, $announcement->{$field} ?? $default);
@endphp

<div class="card stat-card">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-8">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ $old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                    @foreach (\App\Models\Tenant\Announcement::CATEGORIES as $category)
                        <option value="{{ $category }}" {{ $old('category', 'general') === $category ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="body" class="form-label">Message <span class="text-danger">*</span></label>
                <textarea name="body" id="body" rows="6" class="form-control @error('body') is-invalid @enderror" required>{{ $old('body') }}</textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="expires_at" class="form-label">Expires On</label>
                <input type="date" name="expires_at" id="expires_at" class="form-control @error('expires_at') is-invalid @enderror"
                    value="{{ $announcement?->expires_at?->format('Y-m-d') ?? old('expires_at') }}" placeholder="Never">
                @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Leave blank to keep it visible indefinitely.</div>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="hidden" name="is_pinned" value="0">
                    <input type="checkbox" class="form-check-input" id="is_pinned" name="is_pinned" value="1" {{ $old('is_pinned') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_pinned">Pin to top of the board</label>
                </div>
            </div>
        </div>
    </div>
</div>
