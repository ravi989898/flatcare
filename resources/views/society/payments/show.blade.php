@extends('society.layout')

@section('title', $bill->title)

@php
    $statusBadge = ['unpaid' => 'secondary', 'partially_paid' => 'warning', 'paid' => 'success', 'overdue' => 'danger'];
    $methodLabel = ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'upi' => 'UPI', 'cheque' => 'Cheque', 'other' => 'Other'];
@endphp

@section('content')
    <div class="mb-4">
        <a href="{{ route('society.payments.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Payments
        </a>
        <div class="d-flex align-items-center gap-2 mt-2">
            <h1 class="h3 mb-0">{{ $bill->title }}</h1>
            <span class="badge bg-{{ $statusBadge[$bill->status] }}">{{ ucfirst(str_replace('_', ' ', $bill->status)) }}</span>
        </div>
        <p class="text-muted mb-0">{{ $bill->flat?->display_label ?? '—' }} &middot; Due {{ $bill->due_date->format('d M Y') }}</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="row g-3 mb-3">
                <div class="col-4">
                    <div class="card stat-card h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">Billed</div>
                            <div class="fw-semibold">₹{{ number_format($bill->amount, 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card stat-card h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">Paid</div>
                            <div class="fw-semibold text-success">₹{{ number_format($bill->paid_amount, 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card stat-card h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">Balance</div>
                            <div class="fw-semibold {{ $bill->balance > 0 ? 'text-danger' : '' }}">₹{{ number_format($bill->balance, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($bill->notes)
                <div class="card stat-card mb-3">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Notes</div>
                        <p class="mb-0">{{ $bill->notes }}</p>
                    </div>
                </div>
            @endif

            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Payment History</strong></div>
                <div class="card-body p-0">
                    @if ($bill->payments->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach ($bill->payments->sortByDesc('payment_date') as $payment)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">₹{{ number_format($payment->amount, 2) }} &middot; {{ $methodLabel[$payment->payment_method] }}</div>
                                        <div class="text-muted small">
                                            {{ $payment->payment_date->format('d M Y') }}
                                            @if ($payment->reference_number) &middot; Ref: {{ $payment->reference_number }} @endif
                                            @if ($payment->recordedBy) &middot; by {{ $payment->recordedBy->name }} @endif
                                        </div>
                                        @if ($payment->notes)
                                            <div class="text-muted small">{{ $payment->notes }}</div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted p-3 mb-0">No payments recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if ($bill->balance > 0)
                <div class="card stat-card">
                    <div class="card-header bg-white"><strong>Record Payment</strong></div>
                    <div class="card-body p-4">
                        <form action="{{ route('society.payments.pay', $bill->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (₹)</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $bill->balance }}" name="amount" id="amount" class="form-control" value="{{ $bill->balance }}" required>
                                <div class="form-text">Remaining balance: ₹{{ number_format($bill->balance, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date</label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Method</label>
                                <select name="payment_method" id="payment_method" class="form-select">
                                    @foreach (\App\Models\Tenant\Payment::METHODS as $method)
                                        <option value="{{ $method }}">{{ $methodLabel[$method] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="reference_number" class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" id="reference_number" class="form-control" placeholder="Optional — UTR, cheque no., etc.">
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control"></textarea>
                            </div>
                            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-cash-coin"></i> Record Payment</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card stat-card">
                    <div class="card-body p-4 text-center text-success">
                        <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
                        <p class="mb-0 fw-semibold">Fully paid</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop
