@extends('adminlte::page')

@section('title', 'Blocks')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Blocks</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.blocks.create', $society->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Block
            </a>
            <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Society
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Blocks</h3>
        </div>
        <div class="card-body p-0">
            @if ($blocks->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Block Number</th>
                            <th>Floors</th>
                            <th>Flats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blocks as $block)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.societies.blocks.flats.index', [$society->id, $block->id]) }}">{{ $block->name }}</a>
                                </td>
                                <td>{{ $block->block_number }}</td>
                                <td>{{ $block->total_floors ?? '—' }}</td>
                                <td>{{ $block->flats_count }}</td>
                                <td>
                                    @php
                                        $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'under_construction' => 'warning'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$block->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $block->status)) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.societies.blocks.flats.index', [$society->id, $block->id]) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-door-open"></i> Flats
                                    </a>
                                    <a href="{{ route('admin.societies.blocks.edit', [$society->id, $block->id]) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.societies.blocks.destroy', [$society->id, $block->id]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this block?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">
                    <p>No blocks found for this society.</p>
                    <a href="{{ route('admin.societies.blocks.create', $society->id) }}" class="btn btn-primary">
                        Create First Block
                    </a>
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
