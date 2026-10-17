@extends('adminlte::page')

@section('title', 'Permission Logs')

@section('content_header')
    <h1>Permission Logs</h1>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search by person…">
            <x-fc.select name="society_id" placeholder="All societies" :options="$societies->pluck('name', 'id')" />
            <x-fc.select name="role" placeholder="All roles" :options="$roles->pluck('display_name', 'name')" />
            <input type="date" name="from" class="form-control" value="{{ is_string(request('from')) ? request('from') : '' }}" aria-label="From date" title="From">
            <input type="date" name="to" class="form-control" value="{{ is_string(request('to')) ? request('to') : '' }}" aria-label="To date" title="To">
        </x-fc.toolbar>

        @if ($logs->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <th>Society</th>
                            <th>Changed By</th>
                            <th>Changes</th>
                            <x-fc.th sort="when" :default="true" first="desc">When</x-fc.th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>
                                    @if ($log->society)
                                        <div class="fc-person">
                                            <x-fc.avatar :name="$log->society->name" />
                                            <span class="fc-strong">{{ $log->society->name }}</span>
                                        </div>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td>{{ $log->performed_by ?? '—' }}</td>
                                <td>
                                    @php $changes = $log->getChangeSummary(); @endphp
                                    @if (!empty($changes))
                                        <button type="button" class="btn btn-xs btn-outline-secondary" data-toggle="collapse" data-target="#changes-{{ $log->id }}">
                                            <i class="bi bi-chevron-down"></i> {{ count($changes) }} permission(s) changed
                                        </button>
                                        <div id="changes-{{ $log->id }}" class="collapse mt-2">
                                            <table class="table table-sm table-bordered mb-0 bg-white">
                                                <thead>
                                                    <tr><th>Role</th><th>Menu Item</th><th>Before</th><th>After</th></tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($changes as $field => $diff)
                                                        @php [$roleLabel, $itemLabel] = str_contains($field, ' → ') ? explode(' → ', $field, 2) : ['—', $field]; @endphp
                                                        <tr>
                                                            <td>{{ $roleLabel }}</td>
                                                            <td>{{ $itemLabel }}</td>
                                                            <td class="text-danger small">{{ $diff['old'] ? 'Visible' : 'Hidden' }}</td>
                                                            <td class="text-success small">{{ $diff['new'] ? 'Visible' : 'Hidden' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
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
            <x-fc.empty icon="shield-check" title="No permission changes logged yet" text="Menu permission changes made for each society are recorded here." />
        @endif

        <x-fc.footer :paginator="$logs" />
    </div>
@stop
