@extends('society.layout')

@section('title', 'Add Block')

@section('content_header')
    <h1 class="h3 mb-0">Add Block</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>New Block</strong></div>
        <form action="{{ route('society.blocks.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Block Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('block_number') is-invalid @enderror"
                        name="block_number" value="{{ old('block_number') }}" placeholder="e.g. Block-A" autofocus required>
                    @error('block_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('society.blocks.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Create Block</button>
            </div>
        </form>
    </div>
@stop
