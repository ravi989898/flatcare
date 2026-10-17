@extends('society.layout')

@section('title', 'Payments')

@php
    $statusTone = ['unpaid' => 'warning', 'partially_paid' => 'info', 'paid' => 'success', 'overdue' => 'danger'];
    $statusTabs = ['' => ['All', $statusCounts->sum()]];
    foreach (\App\Models\Tenant\MaintenanceBill::STATUSES as $status) {
        $statusTabs[$status] = [ucfirst(str_replace('_', ' ', $status)), $statusCounts->get($status, 0)];
    }
@endphp

@section('content_header')
    <div class="fc-page-head">
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
    <div class="fc-stats">
        <x-fc.stat icon="receipt" :value="'₹'.number_format($summary['total_due'], 2)" label="Total billed" />
        <x-fc.stat icon="cash-coin" tone="success" :value="'₹'.number_format($summary['total_collected'], 2)" label="Total collected" />
        <x-fc.stat icon="hourglass-split" tone="warning" :value="'₹'.number_format(max(0, $summary['total_due'] - $summary['total_collected']), 2)" label="Pending" />
        <x-fc.stat icon="exclamation-triangle" tone="danger" :value="$summary['overdue_count']" label="Overdue bills"
            :href="route('society.payments.index', ['status' => 'overdue'])" />
    </div>

    <div class="fc-card">
        <x-fc.tabs name="status" label="Bill status" :tabs="$statusTabs" />

        <x-fc.toolbar placeholder="Search bill, flat or block…" :keep="['status']">
            <x-fc.select name="block_id" placeholder="All blocks" :options="$blocks" />
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
                            <th class="text-right">Actions</th>
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
                                <td class="fc-nowrap">
                                    {{ $bill->due_date->format('d M Y') }}
                                    @if ($bill->status === 'overdue')
                                        <span class="fc-sub text-danger">{{ $bill->daysOverdue() }} {{ \Illuminate\Support\Str::plural('day', $bill->daysOverdue()) }} late</span>
                                    @endif
                                </td>
                                <td><x-fc.pill :status="$bill->status" :tone="$statusTone[$bill->status] ?? 'neutral'" /></td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="eye" label="View bill" :href="route('society.payments.show', $bill->id)" />
                                        <x-fc.action icon="printer" label="Invoice" :href="route('society.payments.invoice', $bill->id)" target="_blank" rel="noopener" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="receipt" title="No bills yet" text="Bills are created from water readings, or raised here by hand." :keep="['status']"
                :action-url="route('society.payments.create')" action-label="Raise the first bill" />
        @endif

        <x-fc.footer :paginator="$bills" />
    </div>
@stop
