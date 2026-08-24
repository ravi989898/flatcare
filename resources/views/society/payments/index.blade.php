@extends('society.layout')

@section('title', 'Payments')

@php
    $statusBadge = ['unpaid' => 'secondary', 'partially_paid' => 'warning', 'paid' => 'success', 'overdue' => 'danger'];
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Payments</h1>
            <p class="text-muted mb-0">Maintenance dues and collections</p>
        </div>
        <a href="{{ route('society.payments.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Raise Bill
        </a>
    </div>
@stop

@section('content')
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="text-muted small">Total Billed</div>
                        <div class="fw-semibold">₹{{ number_format($summary['total_due'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="text-muted small">Total Collected</div>
                        <div class="fw-semibold">₹{{ number_format($summary['total_collected'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <div class="text-muted small">Overdue Bills</div>
                        <div class="fw-semibold">{{ $summary['overdue_count'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.payments.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="custom-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\MaintenanceBill::STATUSES as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Flat</label>
                    <select name="flat_id" class="custom-select">
                        <option value="">All Flats</option>
                        @foreach ($flats as $flat)
                            <option value="{{ $flat->id }}" {{ request('flat_id') == $flat->id ? 'selected' : '' }}>{{ $flat->display_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
                    @if (request()->anyFilled(['status', 'flat_id']))
                        <a href="{{ route('society.payments.index') }}" class="btn btn-link">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($bills->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Bill</th>
                                <th>Flat</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bills as $bill)
                                <tr>
                                    <td><a href="{{ route('society.payments.show', $bill->id) }}" class="text-decoration-none">{{ $bill->title }}</a></td>
                                    <td>{{ $bill->flat?->display_label ?? '—' }}</td>
                                    <td>₹{{ number_format($bill->amount, 2) }}</td>
                                    <td>₹{{ number_format($bill->paid_amount, 2) }}</td>
                                    <td>₹{{ number_format($bill->balance, 2) }}</td>
                                    <td class="text-muted small">{{ $bill->due_date->format('d M Y') }}</td>
                                    <td><span class="badge bg-{{ $statusBadge[$bill->status] }}">{{ ucfirst(str_replace('_', ' ', $bill->status)) }}</span></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('society.payments.show', $bill->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                            <a href="{{ route('society.payments.invoice', $bill->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-receipt"></i> Invoice
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                    <p class="mb-2">No bills found.</p>
                    <a href="{{ route('society.payments.create') }}" class="btn btn-brand btn-sm">Raise the first bill</a>
                </div>
            @endif
        </div>
    </div>

    @if ($bills->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $bills->links() }}
        </div>
    @endif
@stop
