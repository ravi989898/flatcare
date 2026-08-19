@extends('society.layout')

@section('title', 'Raise Bill')

@section('content')
    <h1 class="h3 mb-4">Raise Bill</h1>

    <form action="{{ route('society.payments.store') }}" method="POST">
        @csrf
        <div class="card stat-card">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="flat_id" class="form-label">Flat <span class="text-danger">*</span></label>
                        <select name="flat_id" id="flat_id" class="form-select @error('flat_id') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            <option value="all" {{ old('flat_id') === 'all' ? 'selected' : '' }}>All active flats ({{ $flats->count() }})</option>
                            @foreach ($flats as $flat)
                                <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>{{ $flat->display_label }}</option>
                            @endforeach
                        </select>
                        @error('flat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-8">
                        <label for="title" class="form-label">Bill Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. August 2026 Maintenance" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" required>
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('society.payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Raise Bill</button>
            </div>
        </div>
    </form>
@stop
