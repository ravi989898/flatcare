@extends('society.layout')

@section('title', 'Extra Charges')

@php
    $statusBadge = ['unpaid' => 'secondary', 'partially_paid' => 'warning', 'paid' => 'success', 'overdue' => 'danger'];
@endphp

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Extra Charges</h1>
            <p class="text-muted mb-0">Function/event usage, hall booking, renovation fund, transfer fee, etc.</p>
        </div>
        <div>
            <a href="{{ route('society.fee-types.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-tags"></i> Manage Fee Types
            </a>
            <a href="{{ route('society.extra-charges.create') }}" class="btn btn-brand">
                <i class="bi bi-plus-lg"></i> Raise Charge
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="text-muted small">Total Charged</div>
                        <div class="font-weight-bold">₹{{ number_format($summary['total_due'], 2) }}</div>
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
                        <div class="font-weight-bold">₹{{ number_format($summary['total_collected'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <div class="text-muted small">Overdue Charges</div>
                        <div class="font-weight-bold">{{ $summary['overdue_count'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card stat-card mb-3">
        <div class="card-body">
            <form action="{{ route('society.extra-charges.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="font-weight-bold mb-1 d-block small text-muted">Status</label>
                    <select name="status" class="custom-select">
                        <option value="">All</option>
                        @foreach (\App\Models\Tenant\MaintenanceBill::STATUSES as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
                    @if (request()->anyFilled(['status']))
                        <a href="{{ route('society.extra-charges.index') }}" class="btn btn-link">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($charges->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Fee Type</th>
                                <th>Flat</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Valid Till</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($charges as $charge)
                                <tr>
                                    <td><a href="{{ route('society.payments.show', $charge->id) }}" class="text-decoration-none">{{ $charge->feeType?->name ?? $charge->title }}</a></td>
                                    <td>{{ $charge->flat?->display_label ?? '—' }}</td>
                                    <td>₹{{ number_format($charge->amount, 2) }}</td>
                                    <td>₹{{ number_format($charge->paid_amount, 2) }}</td>
                                    <td>₹{{ number_format($charge->balance, 2) }}</td>
                                    <td class="text-muted small">{{ $charge->due_date->format('d M Y') }}</td>
                                    <td><span class="badge bg-{{ $statusBadge[$charge->status] }}">{{ ucfirst(str_replace('_', ' ', $charge->status)) }}</span></td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <a href="{{ route('society.payments.show', $charge->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                            <a href="{{ route('society.payments.invoice', $charge->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
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
                    <p class="mb-2">No extra charges raised yet.</p>
                    <a href="{{ route('society.extra-charges.create') }}" class="btn btn-brand btn-sm">Raise the first charge</a>
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
