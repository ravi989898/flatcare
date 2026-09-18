@extends('society.layout')

@section('title', 'Blocks')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Blocks</h1>
            <p class="text-muted mb-0">Society structure - blocks and the flats inside each</p>
        </div>
        <a href="{{ route('society.blocks.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Add Block
        </a>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($blocks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Block Number</th>
                                <th>Flats</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $statusBadge = ['active' => 'success', 'inactive' => 'secondary', 'under_construction' => 'warning']; @endphp
                            @foreach ($blocks as $block)
                                <tr>
                                    <td><a href="{{ route('society.blocks.flats.index', $block->id) }}" class="text-decoration-none">{{ $block->block_number }}</a></td>
                                    <td>{{ $block->flats_count }}</td>
                                    <td><span class="badge bg-{{ $statusBadge[$block->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $block->status)) }}</span></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('society.blocks.flats.index', $block->id) }}" class="btn btn-sm btn-outline-secondary">Flats</a>
                                            <a href="{{ route('society.blocks.edit', $block->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                    <p class="mb-2">No blocks found.</p>
                    <a href="{{ route('society.blocks.create') }}" class="btn btn-brand btn-sm">Create the first block</a>
                </div>
            @endif
        </div>
    </div>

    @if ($blocks->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $blocks->links() }}
        </div>
    @endif
@stop
