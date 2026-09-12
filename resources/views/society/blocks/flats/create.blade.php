@extends('society.layout')

@section('title', 'Add Flats')

@section('content_header')
    <h1 class="h3 mb-0">{{ $block->block_number }} - Add Flats</h1>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-header bg-white"><strong>New Flats</strong></div>
        <form action="{{ route('society.blocks.flats.store', $block->id) }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Flat Numbers <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('flat_numbers') is-invalid @enderror"
                        name="flat_numbers" rows="8" autofocus required
                        placeholder="{{ $block->block_number }}-101&#10;{{ $block->block_number }}-102&#10;{{ $block->block_number }}-103">{{ old('flat_numbers') }}</textarea>
                    <small class="form-text text-muted">One flat number per line (or comma-separated) - add as many as this block has, all at once.</small>
                    @error('flat_numbers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('society.blocks.flats.index', $block->id) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand">Create Flats</button>
            </div>
        </form>
    </div>
@stop
