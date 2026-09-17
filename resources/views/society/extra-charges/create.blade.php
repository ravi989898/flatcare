@extends('society.layout')

@section('title', 'Raise Extra Charge')

@section('content_header')
    <h1 class="h3 mb-0">Raise Extra Charge</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>New Charge</strong></div>
        <form action="{{ route('society.extra-charges.store') }}" method="POST" novalidate>
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Block <span class="text-danger">*</span></label>
                        <select id="block_select" class="custom-select">
                            <option value="">— Select a block —</option>
                            @foreach ($blocks as $block)
                                <option value="{{ $block->id }}">{{ $block->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Select User <span class="text-danger">*</span></label>
                        <select name="flat_id" id="flat_id" class="custom-select @error('flat_id') is-invalid @enderror" data-filtered-by="block_select" required>
                            <option value="">— Select a block first —</option>
                            @foreach ($flats as $flat)
                                @php
                                    $residency = $flat->residents->firstWhere('is_primary', true) ?? $flat->residents->first();
                                    $residentName = $residency?->user?->name;
                                @endphp
                                <option value="{{ $flat->id }}" data-block-id="{{ $flat->block_id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>
                                    {{ $residentName ? "{$residentName} ({$flat->flat_number})" : $flat->flat_number }}
                                </option>
                            @endforeach
                        </select>
                        @error('flat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Fee Type <span class="text-danger">*</span></label>
                        <select name="fee_type_id" id="fee_type_id" class="custom-select @error('fee_type_id') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach ($feeTypes as $feeType)
                                <option value="{{ $feeType->id }}" data-default-amount="{{ $feeType->default_amount }}" {{ old('fee_type_id') == $feeType->id ? 'selected' : '' }}>
                                    {{ $feeType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('fee_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($feeTypes->isEmpty())
                            <small class="form-text text-muted">
                                No fee types yet — <a href="{{ route('society.fee-types.index') }}">add one first</a>.
                            </small>
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Valid Till <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" required>
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold mb-1 d-block">Date</label>
                        <input type="text" class="form-control" value="{{ now()->format('d M Y') }}" readonly>
                        <small class="form-text text-muted">Today — recorded automatically.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="font-weight-bold mb-1 d-block">Notes</label>
                    <textarea name="notes" rows="2" maxlength="1000" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card-footer bg-white">
                <a href="{{ route('society.extra-charges.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Raise Charge</button>
            </div>
        </form>
    </div>
@stop

@push('js')
    <script src="{{ asset('js/extra-charge-fee-autofill.js') }}"></script>
@endpush
