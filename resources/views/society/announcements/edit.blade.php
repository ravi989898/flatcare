@extends('society.layout')

@section('title', 'Edit Announcement')

@section('content_header')
    <h1>Edit Announcement</h1>
@stop

@section('content')
    <form action="{{ route('society.announcements.update', $announcement->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('society.announcements._form', ['announcement' => $announcement])

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('society.announcements.show', $announcement->id) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Save Changes</button>
        </div>
    </form>
@stop
