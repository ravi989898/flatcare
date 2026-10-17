@extends('society.layout')

@section('title', 'Visitors')

@php
    $statusTone = ['pending' => 'warning', 'approved' => 'info', 'checked_in' => 'success', 'checked_out' => 'neutral', 'denied' => 'danger'];
    $purposeLabel = ['guest' => 'Guest', 'delivery' => 'Delivery', 'cab' => 'Cab/Taxi', 'service' => 'Service', 'other' => 'Other'];
    $purposeIcon = ['guest' => 'person', 'delivery' => 'box-seam', 'cab' => 'taxi-front', 'service' => 'tools', 'other' => 'three-dots'];
    $statusTabs = [
        'checked_in' => ['Currently in', $statusCounts->get('checked_in', 0)],
        'pending' => ['Pending', $statusCounts->get('pending', 0)],
        'approved' => ['Approved', $statusCounts->get('approved', 0)],
        'checked_out' => ['Checked out', $statusCounts->get('checked_out', 0)],
        'denied' => ['Denied', $statusCounts->get('denied', 0)],
        'all' => ['All', $statusCounts->sum()],
    ];
@endphp

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Visitors</h1>
            <p class="text-muted mb-0">{{ $currentlyInCount }} currently in the society</p>
        </div>
        <a href="{{ route('society.visitors.create') }}" class="btn btn-brand">
            <i class="bi bi-person-plus"></i> Check In Visitor
        </a>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.tabs name="status" label="Visitor status" :tabs="$statusTabs" :current="$status" />

        <x-fc.toolbar placeholder="Search name, phone, vehicle, flat…" :keep="['status']">
            <x-fc.select name="purpose" placeholder="Any purpose" :options="$purposeLabel" />
        </x-fc.toolbar>

        @if ($visitors->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="name">Visitor</x-fc.th>
                            <th>Visiting</th>
                            <th>Purpose</th>
                            <th>Vehicle</th>
                            <x-fc.th sort="check_in" :default="true" first="desc">Check In</x-fc.th>
                            <x-fc.th sort="check_out" first="desc">Check Out</x-fc.th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visitors as $visitor)
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$visitor->visitor_name" :round="true" />
                                        <div>
                                            <span class="fc-strong">{{ $visitor->visitor_name }}</span>
                                            @if ($visitor->visitor_phone)
                                                <span class="fc-sub">{{ $visitor->visitor_phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="fc-nowrap">
                                    @if ($visitor->flat)
                                        <span class="fc-strong">{{ $visitor->flat->flat_number }}</span>
                                        <span class="fc-sub">{{ $visitor->flat->block?->name }}</span>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td class="fc-nowrap"><i class="bi bi-{{ $purposeIcon[$visitor->purpose] ?? 'three-dots' }} text-muted mr-1"></i>{{ $purposeLabel[$visitor->purpose] ?? ucfirst($visitor->purpose) }}</td>
                                <td class="fc-nowrap">{!! $visitor->vehicle_number ? e($visitor->vehicle_number) : '<span class="fc-dim">—</span>' !!}</td>
                                <td class="fc-nowrap">
                                    @if ($visitor->check_in_at)
                                        {{ $visitor->check_in_at->format('d M, h:i A') }}
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td class="fc-nowrap">
                                    @if ($visitor->check_out_at)
                                        {{ $visitor->check_out_at->format('d M, h:i A') }}
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td><x-fc.pill :status="$visitor->status" :tone="$statusTone[$visitor->status] ?? 'neutral'" /></td>
                                <td>
                                    <div class="fc-actions">
                                        @if ($visitor->isCheckedIn())
                                            <x-fc.post-action :action="route('society.visitors.check_out', $visitor->id)" icon="box-arrow-right" label="Check out" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="person-badge" title="No visitors here" text="Visitors checked in at the gate appear here." :keep="['status']"
                :action-url="route('society.visitors.create')" action-label="Check in a visitor" />
        @endif

        <x-fc.footer :paginator="$visitors" />
    </div>
@stop
