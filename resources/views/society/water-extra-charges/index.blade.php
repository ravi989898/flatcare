@extends('society.layout')

@section('title', 'Extra Charges')

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Extra Charges</h1>
            <p class="text-muted mb-0">Recurring charges added automatically to every flat's water reading bill while they're active.</p>
        </div>
        <div class="fc-page-actions">
            <a href="{{ route('society.water-readings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Water Readings
            </a>
            <a href="{{ route('society.water-extra-charges.create') }}" class="btn btn-brand">
                <i class="bi bi-plus-lg"></i> Add Extra Charges
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search remarks…">
            <x-fc.select name="status" placeholder="Any status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
        </x-fc.toolbar>

        @if ($charges->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="amount" first="desc" class="num">Amount</x-fc.th>
                            <x-fc.th sort="start" :default="true" first="desc">Start Date</x-fc.th>
                            <x-fc.th sort="end" first="desc">End Date</x-fc.th>
                            <th>Remarks</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Updated By</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($charges as $charge)
                            <tr>
                                <td class="num fc-amount">₹{{ number_format($charge->amount, 2) }}</td>
                                <td class="fc-nowrap">{{ $charge->start_date->format('d M Y') }}</td>
                                <td class="fc-nowrap">{!! $charge->end_date ? e($charge->end_date->format('d M Y')) : '<span class="fc-dim">Ongoing</span>' !!}</td>
                                <td>{!! $charge->remarks ? e($charge->remarks) : '<span class="fc-dim">—</span>' !!}</td>
                                <td><x-fc.pill :status="$charge->is_active ? 'active' : 'inactive'" /></td>
                                <td class="fc-nowrap">
                                    {{ $charge->createdBy?->name ?? '—' }}
                                    <span class="fc-sub">{{ $charge->created_at->format('d M Y') }}</span>
                                </td>
                                <td class="fc-nowrap">
                                    {{ $charge->updatedBy?->name ?? '—' }}
                                    <span class="fc-sub">{{ $charge->updated_at->format('d M Y') }}</span>
                                </td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="pencil" label="Edit" :href="route('society.water-extra-charges.edit', $charge->id)" />
                                        @if ($charge->is_active)
                                            <x-fc.post-action :action="route('society.water-extra-charges.deactivate', $charge->id)"
                                                icon="pause-circle" label="Deactivate" tone="warning" confirm="Deactivate this extra charge?" />
                                        @else
                                            <x-fc.post-action :action="route('society.water-extra-charges.activate', $charge->id)"
                                                icon="play-circle" label="Activate" confirm="Activate this extra charge?" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="cash-stack" title="No extra charges yet" text="Recurring charges added here go on every flat's water bill while active."
                :action-url="route('society.water-extra-charges.create')" action-label="Add the first one" />
        @endif

        <x-fc.footer :paginator="$charges" />
    </div>
@stop
