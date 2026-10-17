@extends('society.layout')

@section('title', 'Payment Report')

@php
    $statusTone = ['unpaid' => 'warning', 'partially_paid' => 'info', 'paid' => 'success', 'overdue' => 'danger'];
@endphp

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Payment Report</h1>
            <p class="text-muted mb-0">How much maintenance payment is pending vs. collected</p>
        </div>
        <a href="{{ route('society.reports.payments.export', request()->except(['page', 'per_page'])) }}" class="btn btn-brand">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
    </div>
@stop

@section('content')
    <div class="fc-stats">
        <x-fc.stat icon="receipt" :value="'₹'.number_format($summary['total_billed'], 2)" label="Total billed" />
        <x-fc.stat icon="cash-coin" tone="success" :value="'₹'.number_format($summary['total_collected'], 2)" label="Total collected" />
        <x-fc.stat icon="hourglass-split" tone="warning" :value="'₹'.number_format($summary['total_pending'], 2)" label="Total pending" />
    </div>

    <div class="fc-card">
        <x-fc.toolbar placeholder="Search bill, flat or block…">
            <x-fc.select name="status" placeholder="Any status"
                :options="collect(\App\Models\Tenant\MaintenanceBill::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])" />
            <input type="date" name="from" value="{{ is_string(request('from')) ? request('from') : '' }}" class="form-control" aria-label="Due from" title="Due from">
            <input type="date" name="to" value="{{ is_string(request('to')) ? request('to') : '' }}" class="form-control" aria-label="Due to" title="Due to">
        </x-fc.toolbar>

        @if ($bills->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="title">Bill</x-fc.th>
                            <x-fc.th sort="flat">Flat</x-fc.th>
                            <x-fc.th sort="amount" first="desc" class="num">Amount</x-fc.th>
                            <th class="num">Paid</th>
                            <x-fc.th sort="balance" first="desc" class="num">Balance</x-fc.th>
                            <x-fc.th sort="due" :default="true" first="desc">Due Date</x-fc.th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bills as $bill)
                            <tr>
                                <td><a href="{{ route('society.payments.show', $bill->id) }}" class="fc-strong">{{ $bill->title }}</a></td>
                                <td class="fc-nowrap">
                                    @if ($bill->flat)
                                        <span class="fc-strong">{{ $bill->flat->flat_number }}</span>
                                        <span class="fc-sub">{{ $bill->flat->block?->name }}</span>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td class="num fc-amount">₹{{ number_format($bill->totalAmount(), 2) }}</td>
                                <td class="num">₹{{ number_format($bill->paid_amount, 2) }}</td>
                                <td class="num {{ $bill->balance > 0 ? 'fc-amount' : 'fc-dim' }}">₹{{ number_format($bill->balance, 2) }}</td>
                                <td class="fc-nowrap">{{ $bill->due_date->format('d M Y') }}</td>
                                <td><x-fc.pill :status="$bill->status" :tone="$statusTone[$bill->status] ?? 'neutral'" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="receipt" title="No bills yet" text="Maintenance bills appear here once they are raised." />
        @endif

        <x-fc.footer :paginator="$bills" />
    </div>
@stop
