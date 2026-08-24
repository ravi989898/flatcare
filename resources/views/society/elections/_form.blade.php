@php
    $old = fn ($field, $default = null) => old($field, $election->{$field} ?? $default);
    $oldDateTime = fn ($field) => old($field, $election?->{$field}?->format('Y-m-d\TH:i'));
@endphp

<div class="card stat-card">
    <div class="card-body p-4">
        <div class="row">
            <div class="col-12">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ $old('title') }}" placeholder="e.g. Managing Committee Election 2026" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ $old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="nomination_start_at" class="form-label">Nominations Open</label>
                <input type="datetime-local" name="nomination_start_at" id="nomination_start_at" class="form-control @error('nomination_start_at') is-invalid @enderror" value="{{ $oldDateTime('nomination_start_at') }}">
                @error('nomination_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="nomination_end_at" class="form-label">Nominations Close</label>
                <input type="datetime-local" name="nomination_end_at" id="nomination_end_at" class="form-control @error('nomination_end_at') is-invalid @enderror" value="{{ $oldDateTime('nomination_end_at') }}">
                @error('nomination_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="voting_start_at" class="form-label">Voting Opens</label>
                <input type="datetime-local" name="voting_start_at" id="voting_start_at" class="form-control @error('voting_start_at') is-invalid @enderror" value="{{ $oldDateTime('voting_start_at') }}">
                @error('voting_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="voting_end_at" class="form-label">Voting Closes</label>
                <input type="datetime-local" name="voting_end_at" id="voting_end_at" class="form-control @error('voting_end_at') is-invalid @enderror" value="{{ $oldDateTime('voting_end_at') }}">
                @error('voting_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="alert alert-info mt-3 mb-0 small">
            <i class="bi bi-info-circle"></i>
            These dates are informational — the election actually moves between stages (nominations → voting → closed) via the status buttons on its page, not automatically at these times.
        </div>
    </div>
</div>
