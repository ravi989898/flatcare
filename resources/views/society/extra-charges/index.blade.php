@extends('society.layout')

@section('title', 'Extra Charges')

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
            <h1 class="h3 mb-1">Extra Charges</h1>
            <p class="text-muted mb-0">Function/event usage, hall booking, renovation fund, transfer fee, etc.</p>
        </div>
        <div class="fc-page-actions">
            <a href="{{ route('society.fee-types.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-tags"></i> Manage Fee Types
            </a>
            <a href="{{ route('society.water-extra-charges.index') }}" class="btn btn-outline-info">
                <i class="bi bi-cash-stack"></i> Add Extra Charges
            </a>
            <a href="{{ route('society.extra-charges.create') }}" class="btn btn-brand">
                <i class="bi bi-plus-lg"></i> Raise Charge
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="fc-stats">
        <x-fc.stat icon="receipt" :value="'₹'.number_format($summary['total_due'], 2)" label="Total charged" />
        <x-fc.stat icon="cash-coin" tone="success" :value="'₹'.number_format($summary['total_collected'], 2)" label="Total collected" />
        <x-fc.stat icon="exclamation-triangle" tone="danger" :value="$summary['overdue_count']" label="Overdue charges"
            :href="route('society.extra-charges.index', ['status' => 'overdue'])" />
    </div>

    <div class="fc-card">
        <x-fc.tabs name="status" label="Charge status" :tabs="$statusTabs" />

        <x-fc.toolbar placeholder="Search fee type, flat or block…" :keep="['status']">
            <x-fc.select name="block_id" placeholder="All blocks" :options="$blocks" />
        </x-fc.toolbar>

        @if ($charges->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="title">Fee Type</x-fc.th>
                            <x-fc.th sort="flat">Flat</x-fc.th>
                            <x-fc.th sort="amount" first="desc" class="num">Amount</x-fc.th>
                            <th class="num">Paid</th>
                            <x-fc.th sort="balance" first="desc" class="num">Balance</x-fc.th>
                            <x-fc.th sort="due" :default="true" first="desc">Valid Till</x-fc.th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($charges as $charge)
                            <tr>
                                <td>
                                    <a href="{{ route('society.payments.show', $charge->id) }}" class="fc-strong">{{ $charge->feeType?->name ?? $charge->title }}</a>
                                    @if ($charge->feeType && $charge->title !== $charge->feeType->name)
                                        <span class="fc-sub">{{ $charge->title }}</span>
                                    @endif
                                </td>
                                <td class="fc-nowrap">
                                    @if ($charge->flat)
                                        <span class="fc-strong">{{ $charge->flat->flat_number }}</span>
                                        <span class="fc-sub">{{ $charge->flat->block?->name }}</span>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td class="num fc-amount">₹{{ number_format($charge->amount, 2) }}</td>
                                <td class="num">₹{{ number_format($charge->paid_amount, 2) }}</td>
                                <td class="num {{ $charge->balance > 0 ? 'fc-amount' : 'fc-dim' }}">₹{{ number_format($charge->balance, 2) }}</td>
                                <td class="fc-nowrap">{{ $charge->due_date->format('d M Y') }}</td>
                                <td><x-fc.pill :status="$charge->status" :tone="$statusTone[$charge->status] ?? 'neutral'" /></td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="eye" label="View charge" :href="route('society.payments.show', $charge->id)" />
                                        <x-fc.action icon="printer" label="Invoice" :href="route('society.payments.invoice', $charge->id)" target="_blank" rel="noopener" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="receipt" title="No extra charges yet" :keep="['status']"
                :action-url="route('society.extra-charges.create')" action-label="Raise the first charge" />
        @endif

        <x-fc.footer :paginator="$charges" />
    </div>
@stop
