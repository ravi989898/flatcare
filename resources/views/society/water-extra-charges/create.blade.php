@extends('society.layout')

@section('title', 'Add Extra Charges')

@section('content_header')
    <h1 class="h3 mb-0">Add Extra Charges</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>New Extra Charge</strong></div>
        <form action="{{ route('society.water-extra-charges.store') }}" method="POST" novalidate>
            @csrf
            <div class="card-body">
                <p class="text-muted">Applies to every flat's water reading bill while active.</p>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold mb-1 d-block">End Date</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                        <small class="form-text text-muted">Leave blank for an ongoing charge with no end.</small>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="font-weight-bold mb-1 d-block">Remarks</label>
                    <textarea name="remarks" rows="2" maxlength="1000" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>
                    @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card-footer bg-white">
                <a href="{{ route('society.water-extra-charges.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Add Extra Charges</button>
            </div>
        </form>
    </div>
@stop
