@extends('adminlte::page')

@section('title', 'Edit Flat')

@section('content_header')
    <h1>{{ $society->name }} - {{ $block->name }} - Edit Flat: {{ $flat->flat_number }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Update Flat</h3>
        </div>
        <form action="{{ route('admin.societies.blocks.flats.update', [$society->id, $block->id, $flat->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('admin.societies.blocks.flats._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Flat</button>
                <a href="{{ route('admin.societies.blocks.flats.index', [$society->id, $block->id]) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop
