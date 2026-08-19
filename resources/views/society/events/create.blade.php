@extends('society.layout')

@section('title', 'New Event')

@section('content')
    <h1 class="h3 mb-4">New Event</h1>

    <form action="{{ route('society.events.store') }}" method="POST">
        @csrf
        @include('society.events._form', ['event' => null])

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('society.events.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-brand"><i class="bi bi-calendar-plus"></i> Publish Event</button>
        </div>
    </form>
@stop
