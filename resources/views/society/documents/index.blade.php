@extends('society.layout')

@section('title', 'Documents')

@php
    $categoryLabels = [
        'society' => 'Society Documents',
        'maintenance_bill' => 'Maintenance Bills',
        'notice' => 'Notices & Circulars',
        'legal' => 'Legal Documents',
        'bylaw' => 'By-Laws',
    ];
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Documents</h1>
        <p class="text-muted mb-0">Upload society documents, maintenance bills, notices, legal papers and by-laws for residents to view in the app.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card mb-3">
        <div class="card-header bg-white"><strong>Upload a Document</strong></div>
        <div class="card-body">
            <form action="{{ route('society.documents.store') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small text-muted">Title</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Category</label>
                    <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                        @foreach ($categoryLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">File</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand w-100"><i class="bi bi-upload"></i> Upload</button>
                </div>
                @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
        </div>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link {{ !$category ? 'active' : '' }}" href="{{ route('society.documents.index') }}">All</a></li>
        @foreach ($categoryLabels as $value => $label)
            <li class="nav-item"><a class="nav-link {{ $category === $value ? 'active' : '' }}" href="{{ route('society.documents.index', ['category' => $value]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($documents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>File</th>
                                <th>Uploaded</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $document)
                                <tr>
                                    <td>{{ $document->title }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $categoryLabels[$document->category] ?? $document->category }}</span></td>
                                    <td><a href="{{ Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank">{{ $document->file_name }}</a></td>
                                    <td class="text-muted small">{{ $document->created_at->format('d M Y') }} &middot; {{ $document->uploadedBy?->name ?? '—' }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('society.documents.destroy', $document->id) }}" method="POST" onsubmit="return confirm('Delete this document?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
                    No documents uploaded yet.
                </div>
            @endif
        </div>
    </div>

    @if ($documents->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $documents->links() }}
        </div>
    @endif
@stop
