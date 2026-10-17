@extends('society.layout')

@section('title', 'Blocks')

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="h3 mb-1">Blocks</h1>
            <p class="text-muted mb-0">Society structure - blocks and the flats inside each</p>
        </div>
        <a href="{{ route('society.blocks.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> Add Block
        </a>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search block…">
            <x-fc.select name="status" placeholder="Any status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'under_construction' => 'Under construction']" />
        </x-fc.toolbar>

        @if ($blocks->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="name" :default="true" default-dir="asc">Block</x-fc.th>
                            <x-fc.th sort="flats" first="desc" class="num">Flats</x-fc.th>
                            <x-fc.th sort="status">Status</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blocks as $block)
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$block->block_number" />
                                        <div>
                                            <a href="{{ route('society.blocks.flats.index', $block->id) }}" class="fc-strong">{{ $block->block_number }}</a>
                                            <span class="fc-sub">{{ $block->flats_count }} {{ \Illuminate\Support\Str::plural('flat', $block->flats_count) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="num">{{ $block->flats_count }}</td>
                                <td><x-fc.pill :status="$block->status" /></td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="grid-3x3-gap" label="View flats" :href="route('society.blocks.flats.index', $block->id)" />
                                        <x-fc.action icon="pencil" label="Edit block" :href="route('society.blocks.edit', $block->id)" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="building" title="No blocks yet" text="Add the society's blocks, then the flats inside each."
                :action-url="route('society.blocks.create')" action-label="Create the first block" />
        @endif

        <x-fc.footer :paginator="$blocks" />
    </div>
@stop
