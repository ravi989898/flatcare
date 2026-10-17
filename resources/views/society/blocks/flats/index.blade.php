@extends('society.layout')

@section('title', 'Flats')

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">{{ $block->block_number }} - Flats</h1>
            <p class="text-muted mb-0"><a href="{{ route('society.blocks.index') }}"><i class="bi bi-arrow-left"></i> Back to Blocks</a></p>
        </div>
        <a href="{{ route('society.blocks.flats.create', $block->id) }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Add Flats
        </a>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search flat, resident or owner…">
            <x-fc.select name="ownership_type" placeholder="Any ownership" :options="['owned' => 'Owned', 'rented' => 'Rented', 'vacant' => 'Vacant']" />
            <x-fc.select name="status" placeholder="Any status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'under_construction' => 'Under construction', 'under_maintenance' => 'Under maintenance']" />
        </x-fc.toolbar>

        @if ($flats->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="flat" :default="true" default-dir="asc">Flat</x-fc.th>
                            <th>Resident</th>
                            <x-fc.th sort="ownership">Ownership</x-fc.th>
                            <x-fc.th sort="owner">Owner</x-fc.th>
                            <x-fc.th sort="status">Status</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flats as $flat)
                            @php $residentName = $flat->residents->first()?->user?->name; @endphp
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$block->block_number" />
                                        <div>
                                            <span class="fc-strong">{{ $flat->flat_number }}</span>
                                            @if ($flat->mobile_number)
                                                <span class="fc-sub"><i class="bi bi-phone"></i> {{ $flat->mobile_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{!! $residentName ? e($residentName) : '<span class="fc-dim">—</span>' !!}</td>
                                <td><x-fc.pill :status="$flat->ownership_type" :tone="['owned' => 'success', 'rented' => 'info', 'vacant' => 'neutral'][$flat->ownership_type] ?? 'neutral'" :dot="false" /></td>
                                <td>{!! $flat->owner_name ? e($flat->owner_name) : '<span class="fc-dim">—</span>' !!}</td>
                                <td><x-fc.pill :status="$flat->status" /></td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="pencil" label="Edit flat" :href="route('society.blocks.flats.edit', [$block->id, $flat->id])" />
                                        @if ($flat->status === 'active')
                                            <x-fc.post-action :action="route('society.blocks.flats.toggle_status', [$block->id, $flat->id])"
                                                icon="pause-circle" label="Mark inactive" tone="warning" confirm="Mark this flat inactive?" />
                                        @else
                                            <x-fc.post-action :action="route('society.blocks.flats.toggle_status', [$block->id, $flat->id])"
                                                icon="play-circle" label="Mark active" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="door-open" title="No flats in this block yet"
                :action-url="route('society.blocks.flats.create', $block->id)" action-label="Add Flats" />
        @endif

        <x-fc.footer :paginator="$flats" />
    </div>
@stop
