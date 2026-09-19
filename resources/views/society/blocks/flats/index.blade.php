@extends('society.layout')

@section('title', 'Flats')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">{{ $block->block_number }} - Flats</h1>
            <p class="text-muted mb-0"><a href="{{ route('society.blocks.index') }}">&laquo; Back to Blocks</a></p>
        </div>
        <a href="{{ route('society.blocks.flats.create', $block->id) }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Add Flats
        </a>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($flats->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Flat Number</th>
                                <th>Floor</th>
                                <th>Ownership</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $statusBadge = ['active' => 'success', 'inactive' => 'secondary', 'under_construction' => 'warning', 'under_maintenance' => 'danger']; @endphp
                            @foreach ($flats as $flat)
                                <tr>
                                    <td>{{ $flat->flat_number }}</td>
                                    <td>{{ $flat->floor_number }}</td>
                                    <td>{{ ucfirst($flat->ownership_type) }}</td>
                                    <td>{{ $flat->owner_name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $statusBadge[$flat->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $flat->status)) }}</span></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('society.blocks.flats.edit', [$block->id, $flat->id]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <form action="{{ route('society.blocks.flats.toggle_status', [$block->id, $flat->id]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @if ($flat->status === 'active')
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="Mark this flat inactive?">Deactivate</button>
                                                @else
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                                @endif
                                            </form>
                                            <form action="{{ route('society.blocks.flats.destroy', [$block->id, $flat->id]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this flat?">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-door-open fs-1 d-block mb-2"></i>
                    <p class="mb-2">No flats found in this block.</p>
                    <a href="{{ route('society.blocks.flats.create', $block->id) }}" class="btn btn-brand btn-sm">Add Flats</a>
                </div>
            @endif
        </div>
    </div>

    @if ($flats->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $flats->links() }}
        </div>
    @endif
@stop
