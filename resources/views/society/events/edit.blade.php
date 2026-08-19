@extends('society.layout')

@section('title', 'Edit Event')

@section('content')
    <h1 class="h3 mb-4">Edit Event</h1>

    <form action="{{ route('society.events.update', $event->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('society.events._form', ['event' => $event])

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('society.events.show', $event->id) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Save Changes</button>
        </div>
    </form>
@stop
