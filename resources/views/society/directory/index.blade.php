@extends('society.layout')

@section('title', 'Directory')

@php
    $residentTypeTone = ['owner' => 'success', 'tenant' => 'info', 'occupant' => 'neutral'];
@endphp

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Directory</h1>
            <p class="text-muted mb-0">{{ $residentCount }} resident{{ $residentCount === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('society.directory.create') }}" class="btn btn-brand">
            <i class="bi bi-person-plus"></i> Add Resident
        </a>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search name, phone, email or flat…">
            <x-fc.select name="block_id" placeholder="All blocks" :options="$blocks->pluck('name', 'id')" />
            <x-fc.select name="resident_type" placeholder="Any type" :options="['owner' => 'Owner', 'tenant' => 'Tenant', 'occupant' => 'Occupant']" />
        </x-fc.toolbar>

        @if ($residencies->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="name">Resident</x-fc.th>
                            <x-fc.th sort="flat">Flat</x-fc.th>
                            <th>Type</th>
                            <th>Phone</th>
                            <x-fc.th sort="moved_in" first="desc">Moved In</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($residencies as $residency)
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$residency->user->name" :round="true" />
                                        <div>
                                            <a href="{{ route('society.directory.show', $residency->user_id) }}" class="fc-strong">{{ $residency->user->name }}</a>
                                            @if ($residency->is_primary)
                                                <x-fc.pill tone="brand" :dot="false" label="Primary" class="ml-1" />
                                            @endif
                                            <span class="fc-sub">{{ $residency->user->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="fc-nowrap">
                                    @if ($residency->flat)
                                        <span class="fc-strong">{{ $residency->flat->flat_number }}</span>
                                        <span class="fc-sub">{{ $residency->flat->block?->name }}</span>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td><x-fc.pill :status="$residency->resident_type" :tone="$residentTypeTone[$residency->resident_type] ?? 'neutral'" :dot="false" /></td>
                                <td class="fc-nowrap">
                                    @if ($residency->user->phone)
                                        <a href="tel:{{ $residency->user->phone }}" class="text-reset">{{ $residency->user->phone }}</a>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td class="fc-nowrap">{!! $residency->moved_in_date ? e($residency->moved_in_date->format('d M Y')) : '<span class="fc-dim">—</span>' !!}</td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="eye" label="View resident" :href="route('society.directory.show', $residency->user_id)" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="people" title="No residents yet"
                :action-url="route('society.directory.create')" action-label="Add the first resident" />
        @endif

        <x-fc.footer :paginator="$residencies" />
    </div>
@stop
