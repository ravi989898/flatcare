@extends('adminlte::page')

@section('title', 'Add Flats')

@section('content_header')
    <h1>{{ $society->name }} - {{ $block->name }} - Add Flats</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Flats</h3>
        </div>
        <form action="{{ route('admin.societies.blocks.flats.store', [$society->id, $block->id]) }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="flat_numbers">Flat Numbers <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('flat_numbers') is-invalid @enderror"
                        id="flat_numbers" name="flat_numbers" rows="8" autofocus required
                        placeholder="{{ $block->block_number }}-101&#10;{{ $block->block_number }}-102&#10;{{ $block->block_number }}-103">{{ old('flat_numbers') }}</textarea>
                    <small class="form-text text-muted">One flat number per line (or comma-separated) - add as many as this block has, all at once.</small>
                    @error('flat_numbers')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Flats</button>
                <a href="{{ route('admin.societies.blocks.flats.index', [$society->id, $block->id]) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop
