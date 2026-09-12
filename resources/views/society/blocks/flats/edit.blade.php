@extends('society.layout')

@section('title', 'Edit Flat')

@section('content_header')
    <h1 class="h3 mb-0">{{ $block->block_number }} - Edit Flat</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>{{ $flat->flat_number }}</strong></div>
        <form action="{{ route('society.blocks.flats.update', [$block->id, $flat->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Flat Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('flat_number') is-invalid @enderror"
                        name="flat_number" value="{{ old('flat_number', $flat->flat_number) }}"
                        placeholder="e.g. {{ $block->block_number }}-101" autofocus required>
                    @error('flat_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Resident Mobile Number</label>
                    <input type="text" class="form-control @error('mobile_number') is-invalid @enderror"
                        name="mobile_number" value="{{ old('mobile_number', $flat->mobile_number) }}" placeholder="e.g. 9876543210">
                    <small class="form-text text-muted">
                        This is the OTP-login number for the FlatCare resident app - whoever verifies this number in
                        the app is signed in as this flat's resident. Leave blank if no one should be able to log in yet.
                    </small>
                    @error('mobile_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('society.blocks.flats.index', $block->id) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Save Changes</button>
            </div>
        </form>
    </div>
@stop
