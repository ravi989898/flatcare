@extends('adminlte::page')

@section('title', 'Edit Society')

@section('content_header')
    <h1>Edit {{ $society->name }}</h1>
@stop

@section('content')
    <form action="{{ route('admin.societies.update', $society->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.societies._form', ['society' => $society])

        <div class="card-footer">
            <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>
@stop
