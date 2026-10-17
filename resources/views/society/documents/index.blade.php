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
    $categoryTabs = ['' => ['All', $categoryCounts->sum()]];
    foreach ($categoryLabels as $value => $label) {
        $categoryTabs[$value] = [$label, $categoryCounts->get($value, 0)];
    }
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Documents</h1>
        <p class="text-muted mb-0">Upload society documents, maintenance bills, notices, legal papers and by-laws for residents to view in the app.</p>
    </div>

    <div class="card stat-card mb-3">
        <div class="card-header bg-white"><strong>Upload a Document</strong></div>
        <div class="card-body">
            <form action="{{ route('society.documents.store') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end" novalidate>
                @csrf
                <div class="col-md-4">
                    <label class="form-label small text-muted">Title</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" maxlength="255" required>
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
                    <input
                        type="file"
                        name="file"
                        class="form-control @error('file') is-invalid @enderror"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                        data-validate="file"
                        data-allowed-ext="pdf,doc,docx,xls,xlsx,jpg,jpeg,png"
                        data-max-size-kb="10240"
                        required
                    >
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-brand w-100"><i class="bi bi-upload"></i> Upload</button>
                </div>
                @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
        </div>
    </div>

    <div class="fc-card">
        <x-fc.tabs name="category" label="Document category" :tabs="$categoryTabs" />

        <x-fc.toolbar placeholder="Search title or file name…" :keep="['category']" />

        @if ($documents->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="title">Document</x-fc.th>
                            <x-fc.th sort="category">Category</x-fc.th>
                            <x-fc.th sort="uploaded" :default="true" first="desc">Uploaded</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            @php
                                $url = Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path);
                                $ext = strtolower(pathinfo((string) $document->file_name, PATHINFO_EXTENSION));
                                $icon = match (true) {
                                    $ext === 'pdf' => 'file-earmark-pdf',
                                    in_array($ext, ['doc', 'docx'], true) => 'file-earmark-word',
                                    in_array($ext, ['xls', 'xlsx'], true) => 'file-earmark-excel',
                                    in_array($ext, ['jpg', 'jpeg', 'png'], true) => 'file-earmark-image',
                                    default => 'file-earmark-text',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <span class="fc-avatar"><i class="bi bi-{{ $icon }}"></i></span>
                                        <div>
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="fc-strong">{{ $document->title }}</a>
                                            <span class="fc-sub">{{ $document->file_name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><x-fc.pill tone="neutral" :dot="false" :label="$categoryLabels[$document->category] ?? $document->category" /></td>
                                <td class="fc-nowrap">
                                    {{ $document->created_at->format('d M Y') }}
                                    <span class="fc-sub">{{ $document->uploadedBy?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="box-arrow-up-right" label="Open file" :href="$url" target="_blank" rel="noopener" />
                                        <x-fc.post-action :action="route('society.documents.destroy', $document->id)" method="DELETE"
                                            icon="trash" label="Delete" tone="danger" confirm="Delete this document?" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="file-earmark-text" title="No documents uploaded yet" text="Use the form above to upload the first one." :keep="['category']" />
        @endif

        <x-fc.footer :paginator="$documents" />
    </div>
@stop
