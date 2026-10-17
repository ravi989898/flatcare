@extends('adminlte::page')

@section('title', 'Audit Logs')

@php
    $actionTone = fn (string $action) => match (true) {
        str_contains($action, 'delete') || str_contains($action, 'remove') => 'danger',
        str_contains($action, 'create') || str_contains($action, 'add') => 'success',
        str_contains($action, 'update') || str_contains($action, 'edit') => 'info',
        default => 'neutral',
    };
@endphp

@section('content_header')
    <h1>Audit Logs</h1>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search by person or IP…">
            <x-fc.select name="action" placeholder="All actions" :options="$actions->mapWithKeys(fn ($a) => [$a => str($a)->replace('.', ' ')->headline()->value()])" />
            <x-fc.select name="module" placeholder="All modules" :options="$modules->mapWithKeys(fn ($m) => [$m => str($m)->headline()->value()])" />
            <x-fc.select name="society_id" placeholder="All societies" :options="$societies->pluck('name', 'id')" />
            <input type="date" name="from" class="form-control" value="{{ is_string(request('from')) ? request('from') : '' }}" aria-label="From date" title="From">
            <input type="date" name="to" class="form-control" value="{{ is_string(request('to')) ? request('to') : '' }}" aria-label="To date" title="To">
        </x-fc.toolbar>

        @if ($logs->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Society</th>
                            <th>By</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <x-fc.th sort="when" :default="true" first="desc">When</x-fc.th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>
                                    <x-fc.pill :tone="$actionTone($log->action)" :label="str($log->action)->replace('.', ' ')->headline()->value()" />
                                    <span class="fc-sub mt-1">{{ str($log->module)->headline() }}</span>
                                </td>
                                <td>{!! $log->society ? e($log->society->name) : '<span class="fc-dim">—</span>' !!}</td>
                                <td>{{ $log->superAdmin?->name ?? $log->performed_by ?? '—' }}</td>
                                <td>
                                    @php $changes = $log->getChangeSummary(); @endphp
                                    @if (!empty($changes))
                                        <button type="button" class="btn btn-xs btn-outline-secondary" data-toggle="collapse" data-target="#changes-{{ $log->id }}">
                                            <i class="bi bi-chevron-down"></i> {{ count($changes) }} field(s) changed
                                        </button>
                                        <div id="changes-{{ $log->id }}" class="collapse mt-2">
                                            <table class="table table-sm table-bordered mb-0 bg-white">
                                                <thead>
                                                    <tr><th>Field</th><th>Old</th><th>New</th></tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($changes as $field => $diff)
                                                        <tr>
                                                            <td>{{ $field }}</td>
                                                            <td class="text-danger small">{{ is_scalar($diff['old']) ? $diff['old'] : json_encode($diff['old']) }}</td>
                                                            <td class="text-success small">{{ is_scalar($diff['new']) ? $diff['new'] : json_encode($diff['new']) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @elseif ($log->new_values)
                                        <span class="text-muted small">{{ Str::limit(json_encode($log->new_values), 60) }}</span>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td class="text-muted small fc-nowrap">{{ $log->ip_address ?? '—' }}</td>
                                <td class="fc-nowrap" title="{{ $log->created_at->format('d M Y, h:i:s A') }}">
                                    {{ $log->created_at->format('d M Y, h:i A') }}
                                    <span class="fc-sub">{{ $log->created_at->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="journal-text" title="No audit log entries yet" text="Changes made in the Super Admin panel are recorded here." />
        @endif

        <x-fc.footer :paginator="$logs" />
    </div>
@stop
