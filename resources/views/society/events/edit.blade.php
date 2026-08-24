@extends('society.layout')

@section('title', 'Edit Event')

@section('content_header')
    <h1>Edit Event</h1>
@stop

@section('content')
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
