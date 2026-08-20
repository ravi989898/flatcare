@extends('adminlte::page')

@section('title', 'Add Flat')

@section('content_header')
    <h1>{{ $society->name }} - {{ $block->name }} - Add Flat</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Flat</h3>
        </div>
        <form action="{{ route('admin.societies.blocks.flats.store', [$society->id, $block->id]) }}" method="POST">
            @csrf
            <div class="card-body">
                @include('admin.societies.blocks.flats._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Flat</button>
                <a href="{{ route('admin.societies.blocks.flats.index', [$society->id, $block->id]) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop
