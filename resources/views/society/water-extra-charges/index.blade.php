@extends('society.layout')

@section('title', 'Extra Charges')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Extra Charges</h1>
            <p class="text-muted mb-0">Recurring charges added automatically to every flat's water reading bill while they're active.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('society.water-readings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Water Readings
            </a>
            <a href="{{ route('society.water-extra-charges.create') }}" class="btn btn-brand">
                <i class="bi bi-plus-lg"></i> Add Extra Charges
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($charges->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Remarks</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Updated By</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($charges as $charge)
                                <tr>
                                    <td>₹{{ number_format($charge->amount, 2) }}</td>
                                    <td>{{ $charge->start_date->format('d M Y') }}</td>
                                    <td>{{ $charge->end_date?->format('d M Y') ?? 'Ongoing' }}</td>
                                    <td>{{ $charge->remarks ?: '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $charge->is_active ? 'success' : 'secondary' }}">
                                            {{ $charge->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $charge->createdBy?->name ?? '—' }}
                                        <div class="text-muted small">{{ $charge->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td>
                                        {{ $charge->updatedBy?->name ?? '—' }}
                                        <div class="text-muted small">{{ $charge->updated_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('society.water-extra-charges.edit', $charge->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        @if ($charge->is_active)
                                            <form action="{{ route('society.water-extra-charges.deactivate', $charge->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="Deactivate this extra charge?">Deactivate</button>
                                            </form>
                                        @else
                                            <form action="{{ route('society.water-extra-charges.activate', $charge->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" data-confirm="Activate this extra charge?">Activate</button>
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
                    <i class="bi bi-cash-stack fs-1 d-block mb-2"></i>
                    <p class="mb-2">No extra charges added yet.</p>
                    <a href="{{ route('society.water-extra-charges.create') }}" class="btn btn-brand btn-sm">Add the first one</a>
                </div>
            @endif
        </div>
    </div>

    @if ($charges->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $charges->links() }}
        </div>
    @endif
@stop
