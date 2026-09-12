@extends('society.layout')

@section('title', 'Edit Block')

@section('content_header')
    <h1 class="h3 mb-0">Edit Block</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>{{ $block->block_number }}</strong></div>
        <form action="{{ route('society.blocks.update', $block->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Block Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('block_number') is-invalid @enderror"
                        name="block_number" value="{{ old('block_number', $block->block_number) }}" autofocus required>
                    @error('block_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('society.blocks.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Save Changes</button>
            </div>
        </form>
    </div>
@stop
