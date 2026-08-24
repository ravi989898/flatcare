@extends('society.layout')

@section('title', 'Directory')

@php
    $residentTypeBadge = ['owner' => 'success', 'tenant' => 'info', 'occupant' => 'secondary'];
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Directory</h1>
            <p class="text-muted mb-0">{{ $residentCount }} resident{{ $residentCount === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('society.directory.create') }}" class="btn btn-brand">
            <i class="bi bi-person-plus"></i> Add Resident
        </a>
    </div>
@stop

@section('content')
    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.directory.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-5">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, phone, email or flat number">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Block</label>
                    <select name="block_id" class="custom-select">
                        <option value="">All Blocks</option>
                        @foreach ($blocks as $block)
                            <option value="{{ $block->id }}" {{ request('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
                    @if (request()->anyFilled(['search', 'block_id']))
                        <a href="{{ route('society.directory.index') }}" class="btn btn-link">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($residencies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Flat</th>
                                <th>Type</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Moved In</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($residencies as $residency)
                                <tr>
                                    <td>
                                        <a href="{{ route('society.directory.show', $residency->user_id) }}" class="text-decoration-none">{{ $residency->user->name }}</a>
                                        @if ($residency->is_primary)
                                            <span class="badge bg-brand text-white" style="background:var(--brand)">Primary</span>
                                        @endif
                                    </td>
                                    <td>{{ $residency->flat?->display_label ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $residentTypeBadge[$residency->resident_type] }}">{{ ucfirst($residency->resident_type) }}</span></td>
                                    <td>{{ $residency->user->phone }}</td>
                                    <td>{{ $residency->user->email }}</td>
                                    <td class="text-muted small">{{ $residency->moved_in_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('society.directory.show', $residency->user_id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                    <p class="mb-2">No residents found.</p>
                    <a href="{{ route('society.directory.create') }}" class="btn btn-brand btn-sm">Add the first resident</a>
                </div>
            @endif
        </div>
    </div>

    @if ($residencies->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $residencies->links() }}
        </div>
    @endif
@stop
