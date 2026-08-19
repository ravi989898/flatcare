@extends('adminlte::page')

@section('title', 'New Society')

@section('content_header')
    <h1>New Society</h1>
@stop

@section('content')
    <form action="{{ route('admin.societies.store') }}" method="POST">
        @csrf
        @include('admin.societies._form', ['society' => null, 'modules' => $modules])

        <div class="card-footer">
            <a href="{{ route('admin.societies.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Society</button>
        </div>
    </form>
@stop
