@extends('society.layout')

@section('title', 'Visitors')

@php
    $statusBadge = ['checked_in' => 'success', 'checked_out' => 'secondary', 'denied' => 'danger'];
    $purposeLabel = ['guest' => 'Guest', 'delivery' => 'Delivery', 'cab' => 'Cab/Taxi', 'service' => 'Service', 'other' => 'Other'];
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Visitors</h1>
            <p class="text-muted mb-0">{{ $currentlyInCount }} currently in the society</p>
        </div>
        <a href="{{ route('society.visitors.create') }}" class="btn btn-brand">
            <i class="bi bi-person-plus"></i> Check In Visitor
        </a>
    </div>
@stop

@section('content')
    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.visitors.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, phone or vehicle number">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="custom-select">
                        <option value="checked_in" {{ $status === 'checked_in' ? 'selected' : '' }}>Currently In</option>
                        <option value="checked_out" {{ $status === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
                    @if (request('search'))
                        <a href="{{ route('society.visitors.index', ['status' => $status]) }}" class="btn btn-link">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($visitors->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Visiting</th>
                                <th>Purpose</th>
                                <th>Vehicle</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visitors as $visitor)
                                <tr>
                                    <td>
                                        {{ $visitor->visitor_name }}
                                        @if ($visitor->visitor_phone)
                                            <div class="text-muted small">{{ $visitor->visitor_phone }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $visitor->flat?->display_label ?? '—' }}</td>
                                    <td>{{ $purposeLabel[$visitor->purpose] }}</td>
                                    <td>{{ $visitor->vehicle_number ?: '—' }}</td>
                                    <td class="text-muted small">{{ $visitor->check_in_at?->format('d M, h:i A') }}</td>
                                    <td class="text-muted small">{{ $visitor->check_out_at?->format('d M, h:i A') ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $statusBadge[$visitor->status] }}">{{ ucfirst(str_replace('_', ' ', $visitor->status)) }}</span></td>
                                    <td class="text-end">
                                        @if ($visitor->isCheckedIn())
                                            <form action="{{ route('society.visitors.check_out', $visitor->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-box-arrow-right"></i> Check Out
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-person-badge fs-1 d-block mb-2"></i>
                    <p class="mb-2">No visitors found.</p>
                    <a href="{{ route('society.visitors.create') }}" class="btn btn-brand btn-sm">Check in a visitor</a>
                </div>
            @endif
        </div>
    </div>

    @if ($visitors->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $visitors->links() }}
        </div>
    @endif
@stop
