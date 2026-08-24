@extends('society.layout')

@section('title', 'Add Water Reading')

@section('content_header')
    <h1 class="h3 mb-1">Add Water Reading</h1>
    @if ($block)
        <p class="text-muted mb-0">
            {{ $block->name }} — enter this month's meter reading per flat. The bill is calculated automatically:
            units &times; ₹{{ number_format($society->water_unit_rate ?? 0, 2) }}/unit + ₹{{ number_format($society->fixed_maintenance ?? 0, 2) }} fixed maintenance.
        </p>
    @else
        <p class="text-muted mb-0">Choose a block to enter this month's meter readings for its flats.</p>
    @endif
@stop

@section('content')
    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.water-readings.create') }}" method="GET" class="row align-items-end">
                @if ($block)
                    <input type="hidden" name="block_id" value="{{ $block->id }}">
                @endif
                <div class="col-md-4">
                    <label class="form-label small text-muted">Billing Month</label>
                    <input type="month" name="month" class="form-control js-auto-submit" value="{{ $month->format('Y-m') }}">
                </div>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (!$block)
        {{-- Step 1: pick a block --}}
        @if ($blocks->count() > 0)
            <div class="row">
                @foreach ($blocks as $b)
                    <div class="col-md-3 col-sm-4 col-6">
                        <a href="{{ route('society.water-readings.create', ['month' => $month->format('Y-m'), 'block_id' => $b->id]) }}"
                            class="card stat-card text-decoration-none h-100">
                            <div class="card-body text-center py-4">
                                <i class="bi bi-building fs-2 text-brand d-block mb-2"></i>
                                <div class="fw-semibold text-dark">{{ $b->name }}</div>
                                <div class="text-muted small">{{ $b->flats_count }} flat{{ $b->flats_count === 1 ? '' : 's' }}</div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card stat-card">
                <div class="card-body p-5 text-center text-muted">
                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                    No blocks found. Add a block under Maintenance setup first.
                </div>
            </div>
        @endif
    @else
        {{-- Step 2: enter readings for every flat in the chosen block --}}
        <div class="mb-3">
            <a href="{{ route('society.water-readings.create', ['month' => $month->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Choose a different block
            </a>
        </div>

        <form action="{{ route('society.water-readings.store') }}" method="POST">
            @csrf
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <input type="hidden" name="block_id" value="{{ $block->id }}">

            <div class="card stat-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Flat</th>
                                    <th style="width: 180px;">Previous Reading</th>
                                    <th style="width: 180px;">Current Reading</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $i => $row)
                                    <tr>
                                        <td>
                                            {{ $row['flat']->flat_number }}
                                            <input type="hidden" name="readings[{{ $i }}][flat_id]" value="{{ $row['flat']->id }}">
                                        </td>
                                        <td>
                                            @if ($row['has_history'])
                                                <input type="text" class="form-control-plaintext" value="{{ $row['previous_reading'] }}" readonly>
                                                <input type="hidden" name="readings[{{ $i }}][previous_reading]" value="{{ old("readings.$i.previous_reading", $row['previous_reading']) }}">
                                            @else
                                                <input type="number" step="0.01" min="0" name="readings[{{ $i }}][previous_reading]"
                                                    class="form-control form-control-sm @error("readings.$i.previous_reading") is-invalid @enderror"
                                                    value="{{ old("readings.$i.previous_reading", $row['previous_reading']) }}"
                                                    placeholder="Starting reading">
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="readings[{{ $i }}][current_reading]"
                                                class="form-control form-control-sm @error("readings.$i.current_reading") is-invalid @enderror"
                                                value="{{ old("readings.$i.current_reading", $row['current_reading']) }}">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted p-4">No active flats in this block.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <a href="{{ route('society.water-readings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> Save Readings &amp; Generate Bills</button>
                </div>
            </div>
        </form>
    @endif
@stop
