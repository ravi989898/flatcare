@extends('adminlte::page')

@section('title', 'Permission Logs')

@section('content_header')
    <h1>Permission Logs</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form action="{{ route('admin.permission_logs.index') }}" method="GET" class="form-inline">
                <select name="society_id" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">All Societies</option>
                    @foreach ($societies as $society)
                        <option value="{{ $society->id }}" {{ (string) request('society_id') === (string) $society->id ? 'selected' : '' }}>{{ $society->name }}</option>
                    @endforeach
                </select>
                <select name="role" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">All Roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ $role->display_name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" class="form-control form-control-sm mr-2 mb-1" value="{{ request('from') }}" placeholder="From">
                <input type="date" name="to" class="form-control form-control-sm mr-2 mb-1" value="{{ request('to') }}" placeholder="To">
                <button type="submit" class="btn btn-sm btn-secondary mb-1"><i class="fas fa-search"></i> Filter</button>
                @if (request()->anyFilled(['society_id', 'role', 'from', 'to']))
                    <a href="{{ route('admin.permission_logs.index') }}" class="btn btn-sm btn-link mb-1">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            @if ($logs->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Society</th>
                            <th>Changed By</th>
                            <th>Changes</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ $log->society?->name ?? '—' }}</td>
                                <td>{{ $log->performed_by ?? '—' }}</td>
                                <td>
                                    @php $changes = $log->getChangeSummary(); @endphp
                                    @if (!empty($changes))
                                        <button type="button" class="btn btn-xs btn-outline-secondary" data-toggle="collapse" data-target="#changes-{{ $log->id }}">
                                            {{ count($changes) }} permission(s) changed
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
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">No permission changes logged yet.</div>
            @endif
        </div>
    </div>

    @if ($logs->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $logs->links() }}
        </div>
    @endif
@stop
