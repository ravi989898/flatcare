@extends('society.layout')

@section('title', 'Water Readings')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Water Readings</h1>
            <p class="text-muted mb-0">Monthly meter readings and the maintenance bills generated from them</p>
        </div>
        <a href="{{ route('society.water-readings.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Add Reading
        </a>
    </div>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($readings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Flat</th>
                                <th>Previous</th>
                                <th>Current</th>
                                <th>Units</th>
                                <th>Bill Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($readings as $reading)
                                <tr>
                                    <td>{{ $reading->reading_month->format('F Y') }}</td>
                                    <td>{{ $reading->flat?->display_label ?? '—' }}</td>
                                    <td>{{ $reading->previous_reading }}</td>
                                    <td>{{ $reading->current_reading }}</td>
                                    <td>{{ $reading->units }}</td>
                                    <td>
                                        @if ($reading->bill)
                                            ₹{{ number_format($reading->bill->amount, 2) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($reading->bill)
                                            <a href="{{ route('society.payments.show', $reading->bill->id) }}" class="btn btn-sm btn-outline-secondary">View Bill</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-droplet fs-1 d-block mb-2"></i>
                    <p class="mb-2">No water readings recorded yet.</p>
                    <a href="{{ route('society.water-readings.create') }}" class="btn btn-brand btn-sm">Add the first reading</a>
                </div>
            @endif
        </div>
    </div>

    @if ($readings->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $readings->links() }}
        </div>
    @endif
@stop
