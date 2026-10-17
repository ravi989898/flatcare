@extends('society.layout')

@section('title', 'Water Readings')

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Water Readings</h1>
            <p class="text-muted mb-0">Monthly meter readings and the maintenance bills generated from them</p>
        </div>
        <div class="fc-page-actions">
            <a href="{{ route('society.water-extra-charges.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-cash-stack"></i> Add Extra Charges
            </a>
            <a href="{{ route('society.water-readings.create') }}" class="btn btn-brand">
                <i class="bi bi-plus-lg"></i> Add Reading
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="fc-stats">
        <x-fc.stat icon="droplet" :value="number_format($summary['count'])" label="Readings" />
        <x-fc.stat icon="speedometer2" tone="info" :value="number_format($summary['units'], 2)" label="Units used" />
        <x-fc.stat icon="receipt" tone="success" :value="'₹'.number_format($summary['billed'], 2)" label="Total billed" />
    </div>

    <div class="fc-card">
        <x-fc.toolbar placeholder="Search flat or block…">
            <x-fc.select name="month" placeholder="All months" :options="$months" />
            <x-fc.select name="block_id" placeholder="All blocks" :options="$blocks" />
        </x-fc.toolbar>

        @if ($readings->count() > 0)
                <div class="table-responsive">
                    <table class="table fc-table">
                        <thead>
                            <tr>
                                <x-fc.th sort="month" :default="true" first="desc">Month</x-fc.th>
                                <x-fc.th sort="flat">Flat</x-fc.th>
                                <th class="num">Previous</th>
                                <th class="num">Current</th>
                                <x-fc.th sort="units" first="desc" class="num">Units</x-fc.th>
                                <th class="num">Extra</th>
                                <x-fc.th sort="amount" first="desc" class="num">Bill Amount</x-fc.th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($readings as $reading)
                                @php $editable = ! $reading->bill || $reading->bill->payments->isEmpty(); @endphp
                                <tr>
                                    <td class="fc-nowrap">{{ $reading->reading_month->format('F Y') }}</td>
                                    <td>
                                        <div class="fc-person">
                                            <x-fc.avatar :name="$reading->flat?->block?->block_number ?? $reading->flat?->flat_number" />
                                            <div>
                                                <span class="fc-strong">{{ $reading->flat?->flat_number ?? '—' }}</span>
                                                <span class="fc-sub">{{ $reading->flat?->block?->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num">{{ $reading->previous_reading }}</td>
                                    <td class="num">{{ $reading->current_reading }}</td>
                                    <td class="num">{{ rtrim(rtrim(number_format($reading->units, 2), '0'), '.') }}</td>
                                    <td class="num">{{ (float) $reading->extra_amount > 0 ? '₹'.number_format($reading->extra_amount, 2) : '—' }}</td>
                                    <td class="num">
                                        @if ($reading->bill)
                                            <span class="fc-amount">₹{{ number_format($reading->bill->amount, 2) }}</span>
                                        @else
                                            <span class="fc-dim">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($reading->bill)
                                            <x-fc.pill :status="$reading->bill->status" />
                                        @else
                                            <x-fc.pill status="no_bill" label="No bill" />
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fc-actions">
                                            @if ($editable)
                                                <x-fc.action icon="pencil" label="Edit reading" data-toggle="modal" data-target="#editReadingModal{{ $reading->id }}" />
                                            @else
                                                <x-fc.action icon="lock" label="Paid bills can't be edited" :disabled="true" />
                                            @endif
                                            @if ($reading->bill)
                                                <x-fc.action icon="receipt" label="View bill" :href="route('society.payments.show', $reading->bill->id)" />
                                            @endif
                                        </div>
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
            <x-fc.empty icon="droplet" title="No water readings yet" text="Add this month's meter readings to generate maintenance bills."
                :action-url="route('society.water-readings.create')" action-label="Add the first reading" />
        @endif

        <x-fc.footer :paginator="$readings" />
    </div>
@stop
