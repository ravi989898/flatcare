@extends('society.layout')

@section('title', 'New Announcement')

@section('content')
    <h1 class="h3 mb-4">New Announcement</h1>

    <form action="{{ route('society.announcements.store') }}" method="POST">
        @csrf
        @include('society.announcements._form', ['announcement' => null])

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('society.announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-brand"><i class="bi bi-megaphone"></i> Publish</button>
        </div>
    </form>
@stop
