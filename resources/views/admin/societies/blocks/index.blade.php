@extends('adminlte::page')

@section('title', 'Blocks')

@section('content_header')
    <div class="fc-page-head">
        <h1 class="mb-0">{{ $society->name }} - Blocks</h1>
        <div class="fc-page-actions">
            <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Society
            </a>
            <a href="{{ route('admin.societies.blocks.create', $society->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Block
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
    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

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
                                        <a href="{{ route('admin.societies.blocks.flats.index', [$society->id, $block->id]) }}" class="fc-strong">{{ $block->block_number }}</a>
                                    </div>
                                </td>
                                <td class="num">{{ $block->flats_count }}</td>
                                <td><x-fc.pill :status="$block->status" /></td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="grid-3x3-gap" label="View flats" :href="route('admin.societies.blocks.flats.index', [$society->id, $block->id])" />
                                        <x-fc.action icon="pencil" label="Edit block" :href="route('admin.societies.blocks.edit', [$society->id, $block->id])" />
                                        @if ($block->status === 'active')
                                            <x-fc.post-action :action="route('admin.societies.blocks.toggle_status', [$society->id, $block->id])"
                                                icon="pause-circle" label="Mark inactive" tone="warning" confirm="Mark this block inactive?" />
                                        @else
                                            <x-fc.post-action :action="route('admin.societies.blocks.toggle_status', [$society->id, $block->id])"
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
            <x-fc.empty icon="building" title="No blocks for this society yet">
                <a href="{{ route('admin.societies.blocks.create', $society->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create First Block</a>
            </x-fc.empty>
        @endif

        <x-fc.footer :paginator="$blocks" />
    </div>
@stop
