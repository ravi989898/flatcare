@extends('adminlte::page')

@section('title', 'Edit Role')

@section('content_header')
    <h1>Edit Role — {{ $role->display_name }}</h1>
@stop

@section('content')
    <div class="card">
        <form action="{{ route('admin.settings.roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('admin.settings.roles._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <a href="{{ route('admin.settings.roles.index') }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
@stop
