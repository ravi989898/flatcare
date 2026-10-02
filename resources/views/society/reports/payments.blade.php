@extends('society.layout')

@section('title', 'Payment Report')

@php
    $statusBadge = ['unpaid' => 'secondary', 'partially_paid' => 'warning', 'paid' => 'success', 'overdue' => 'danger'];
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Payment Report</h1>
            <p class="text-muted mb-0">How much maintenance payment is pending vs. collected</p>
        </div>
        <a href="{{ route('society.reports.payments.export', request()->query()) }}" class="btn btn-brand">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
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
                        <div class="fw-semibold">₹{{ number_format($summary['total_billed'], 2) }}</div>
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
                        <div class="text-muted small">Total Pending</div>
                        <div class="fw-semibold">₹{{ number_format($summary['total_pending'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.reports.payments') }}" method="GET" class="row align-items-end gy-2">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="custom-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\MaintenanceBill::STATUSES as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Due From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Due To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
                    @if (request()->anyFilled(['status', 'from', 'to']))
                        <a href="{{ route('society.reports.payments') }}" class="btn btn-link">Clear</a>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                    <p class="mb-0">No bills match these filters.</p>
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
