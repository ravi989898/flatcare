@extends('society.layout')

@section('title', 'New Election')

@section('content')
    <h1 class="h3 mb-4">New Election</h1>

    <form action="{{ route('society.elections.store') }}" method="POST">
        @csrf
        @include('society.elections._form', ['election' => null])

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('society.elections.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Create Election</button>
        </div>
    </form>
@stop
