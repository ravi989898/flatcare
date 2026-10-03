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
                                        @if (! $reading->bill || $reading->bill->payments->isEmpty())
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#editReadingModal{{ $reading->id }}">Edit</button>
                                        @endif
                                        @if ($reading->bill)
                                            <a href="{{ route('society.payments.show', $reading->bill->id) }}" class="btn btn-sm btn-outline-secondary">View Bill</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- One modal per editable row, rendered outside the table so
                     Bootstrap's fixed-position overlay isn't nested inside a
                     <td>. Each posts only current_reading — previous_reading
                     is read server-side off the existing row, never trusted
                     from the request. --}}
                @foreach ($readings as $reading)
                    @if (! $reading->bill || $reading->bill->payments->isEmpty())
                        <div class="modal fade" id="editReadingModal{{ $reading->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('society.water-readings.update', $reading->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Reading — {{ $reading->flat?->display_label ?? '—' }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label class="small text-muted mb-1">Month</label>
                                                <input type="text" class="form-control-plaintext py-0" value="{{ $reading->reading_month->format('F Y') }}" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label class="small text-muted mb-1">Previous Reading</label>
                                                <input type="text" class="form-control-plaintext py-0" value="{{ $reading->previous_reading }}" readonly>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label for="current_reading_{{ $reading->id }}">Current Reading</label>
                                                <input type="number" step="0.01" min="0" class="form-control"
                                                    id="current_reading_{{ $reading->id }}" name="current_reading"
                                                    value="{{ $reading->current_reading }}" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-brand">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
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
