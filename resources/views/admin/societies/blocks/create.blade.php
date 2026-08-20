@extends('adminlte::page')

@section('title', 'Add Block')

@section('content_header')
    <h1>{{ $society->name }} - Add Block</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Block</h3>
        </div>
        <form action="{{ route('admin.societies.blocks.store', $society->id) }}" method="POST">
            @csrf
            <div class="card-body">
                @include('admin.societies.blocks._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Block</button>
                <a href="{{ route('admin.societies.blocks.index', $society->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop
