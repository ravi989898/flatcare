@extends('adminlte::page')

@section('title', 'Flats')

@section('content_header')
    <div class="fc-page-head">
        <h1 class="mb-0">{{ $society->name }} - {{ $block->name }} - Flats</h1>
        <div class="fc-page-actions">
            <a href="{{ route('admin.societies.blocks.index', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Blocks
            </a>
            <a href="{{ route('admin.societies.blocks.flats.create', [$society->id, $block->id]) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Flats
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <div class="fc-card">
        <x-fc.toolbar placeholder="Search flat, owner or mobile…">
            <x-fc.select name="ownership_type" placeholder="Any ownership" :options="['owned' => 'Owned', 'rented' => 'Rented', 'vacant' => 'Vacant']" />
            <x-fc.select name="status" placeholder="Any status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'under_construction' => 'Under construction', 'under_maintenance' => 'Under maintenance']" />
        </x-fc.toolbar>

        @if ($flats->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="flat" :default="true" default-dir="asc">Flat</x-fc.th>
                            <x-fc.th sort="floor" class="num">Floor</x-fc.th>
                            <th>Ownership</th>
                            <x-fc.th sort="owner">Owner</x-fc.th>
                            <x-fc.th sort="status">Status</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flats as $flat)
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
                                <td class="num">{{ $flat->floor_number }}</td>
                                <td><x-fc.pill :status="$flat->ownership_type" :tone="['owned' => 'success', 'rented' => 'info', 'vacant' => 'neutral'][$flat->ownership_type] ?? 'neutral'" :dot="false" /></td>
                                <td>{!! $flat->owner_name ? e($flat->owner_name) : '<span class="fc-dim">—</span>' !!}</td>
                                <td><x-fc.pill :status="$flat->status" /></td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="pencil" label="Edit flat" :href="route('admin.societies.blocks.flats.edit', [$society->id, $block->id, $flat->id])" />
                                        @if ($flat->status === 'active')
                                            <x-fc.post-action :action="route('admin.societies.blocks.flats.toggle_status', [$society->id, $block->id, $flat->id])"
                                                icon="pause-circle" label="Mark inactive" tone="warning" confirm="Mark this flat inactive?" />
                                        @else
                                            <x-fc.post-action :action="route('admin.societies.blocks.flats.toggle_status', [$society->id, $block->id, $flat->id])"
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
            <x-fc.empty icon="door-open" title="No flats in this block yet">
                <a href="{{ route('admin.societies.blocks.flats.create', [$society->id, $block->id]) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Flats</a>
            </x-fc.empty>
        @endif

        <x-fc.footer :paginator="$flats" />
    </div>
@stop
