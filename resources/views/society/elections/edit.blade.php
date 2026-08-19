@extends('society.layout')

@section('title', 'Edit Election')

@section('content')
    <h1 class="h3 mb-4">Edit Election</h1>

    <form action="{{ route('society.elections.update', $election->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('society.elections._form', ['election' => $election])

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('society.elections.show', $election->id) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Save Changes</button>
        </div>
    </form>
@stop
