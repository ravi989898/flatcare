@extends('society.layout')

@section('title', 'Check In Visitor')

@section('content_header')
    <h1>Check In Visitor</h1>
@stop

@section('content')
    <form action="{{ route('society.visitors.store') }}" method="POST">
        @csrf
        <div class="card stat-card">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <label for="flat_id" class="form-label">Visiting Flat <span class="text-danger">*</span></label>
                        <select name="flat_id" id="flat_id" class="custom-select @error('flat_id') is-invalid @enderror" required>
                            <option value="">— Select a flat —</option>
                            @foreach ($flats as $flat)
                                <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>{{ $flat->display_label }}</option>
                            @endforeach
                        </select>
                        @error('flat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                        <select name="purpose" id="purpose" class="custom-select @error('purpose') is-invalid @enderror" required>
                            @foreach (\App\Models\Tenant\Visitor::PURPOSES as $purpose)
                                <option value="{{ $purpose }}" {{ old('purpose', 'guest') === $purpose ? 'selected' : '' }}>{{ ucfirst($purpose) }}</option>
                            @endforeach
                        </select>
                        @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="visitor_name" class="form-label">Visitor Name <span class="text-danger">*</span></label>
                        <input type="text" name="visitor_name" id="visitor_name" class="form-control @error('visitor_name') is-invalid @enderror" value="{{ old('visitor_name') }}" required>
                        @error('visitor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="visitor_phone" class="form-label">Phone</label>
                        <input type="text" name="visitor_phone" id="visitor_phone" class="form-control @error('visitor_phone') is-invalid @enderror" value="{{ old('visitor_phone') }}">
                        @error('visitor_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="vehicle_number" class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control @error('vehicle_number') is-invalid @enderror" value="{{ old('vehicle_number') }}" placeholder="Optional">
                        @error('vehicle_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('society.visitors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand"><i class="bi bi-box-arrow-in-right"></i> Check In</button>
            </div>
        </div>
    </form>
@stop
