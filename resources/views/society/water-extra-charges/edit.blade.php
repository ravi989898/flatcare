@extends('society.layout')

@section('title', 'Edit Extra Charge')

@section('content_header')
    <h1 class="h3 mb-0">Edit Extra Charge</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>Extra Charge</strong></div>
        <form action="{{ route('society.water-extra-charges.update', $charge->id) }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            <div class="card-body">
                <p class="text-muted">Applies to every flat's water reading bill while active.</p>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $charge->amount) }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $charge->start_date?->format('Y-m-d')) }}" required>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold mb-1 d-block">End Date</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $charge->end_date?->format('Y-m-d')) }}">
                        <small class="form-text text-muted">Leave blank for an ongoing charge with no end.</small>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block small text-muted">Created</label>
                        <input type="text" class="form-control-plaintext py-0" value="{{ $charge->createdBy?->name ?? '—' }} · {{ $charge->created_at->format('d M Y') }}" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block small text-muted">Last Updated</label>
                        <input type="text" class="form-control-plaintext py-0" value="{{ $charge->updatedBy?->name ?? '—' }} · {{ $charge->updated_at->format('d M Y') }}" readonly>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white">
                <a href="{{ route('society.water-extra-charges.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Save Changes</button>
            </div>
        </form>
    </div>
@stop
