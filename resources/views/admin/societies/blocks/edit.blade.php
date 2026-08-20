@extends('adminlte::page')

@section('title', 'Edit Block')

@section('content_header')
    <h1>{{ $society->name }} - Edit Block: {{ $block->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Update Block</h3>
        </div>
        <form action="{{ route('admin.societies.blocks.update', [$society->id, $block->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('admin.societies.blocks._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Block</button>
                <a href="{{ route('admin.societies.blocks.index', $society->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop
