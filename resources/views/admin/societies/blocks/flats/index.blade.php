@extends('adminlte::page')

@section('title', 'Flats')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - {{ $block->name }} - Flats</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.blocks.flats.create', [$society->id, $block->id]) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Flats
            </a>
            <a href="{{ route('admin.societies.blocks.index', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Blocks
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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Flats in {{ $block->name }}</h3>
        </div>
        <div class="card-body p-0">
            @if ($flats->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Flat Number</th>
                            <th>Floor</th>
                            <th>Ownership</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flats as $flat)
                            <tr>
                                <td>{{ $flat->flat_number }}</td>
                                <td>{{ $flat->floor_number }}</td>
                                <td>{{ ucfirst($flat->ownership_type) }}</td>
                                <td>{{ $flat->owner_name ?? '—' }}</td>
                                <td>
                                    @php
                                        $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'under_construction' => 'warning', 'under_maintenance' => 'danger'];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$flat->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $flat->status)) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.societies.blocks.flats.edit', [$society->id, $block->id, $flat->id]) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.societies.blocks.flats.toggle_status', [$society->id, $block->id, $flat->id]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @if ($flat->status === 'active')
                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Mark this flat inactive?')">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                        @endif
                                    </form>
                                    <form action="{{ route('admin.societies.blocks.flats.destroy', [$society->id, $block->id, $flat->id]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this flat?')">
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
                    <p>No flats found in this block.</p>
                    <a href="{{ route('admin.societies.blocks.flats.create', [$society->id, $block->id]) }}" class="btn btn-primary">
                        Add Flats
                    </a>
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
