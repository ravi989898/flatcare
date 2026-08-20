@extends('adminlte::page')

@section('title', 'New Role')

@section('content_header')
    <h1>New Role</h1>
@stop

@section('content')
    <div class="card">
        <form action="{{ route('admin.settings.roles.store') }}" method="POST">
            @csrf
            <div class="card-body">
                @include('admin.settings.roles._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Role</button>
                <a href="{{ route('admin.settings.roles.index') }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
@stop
