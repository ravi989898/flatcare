@extends('adminlte::page')

@section('title', 'Audit Logs')

@section('content_header')
    <h1>Audit Logs</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form action="{{ route('admin.audit_logs.index') }}" method="GET" class="form-inline">
                <select name="action" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">All Actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ str($action)->headline() }}</option>
                    @endforeach
                </select>
                <select name="module" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">All Modules</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ str($module)->headline() }}</option>
                    @endforeach
                </select>
                <select name="society_id" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">All Societies</option>
                    @foreach ($societies as $society)
                        <option value="{{ $society->id }}" {{ (string) request('society_id') === (string) $society->id ? 'selected' : '' }}>{{ $society->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" class="form-control form-control-sm mr-2 mb-1" value="{{ request('from') }}" placeholder="From">
                <input type="date" name="to" class="form-control form-control-sm mr-2 mb-1" value="{{ request('to') }}" placeholder="To">
                <button type="submit" class="btn btn-sm btn-secondary mb-1"><i class="fas fa-search"></i> Filter</button>
                @if (request()->anyFilled(['action', 'module', 'society_id', 'from', 'to']))
                    <a href="{{ route('admin.audit_logs.index') }}" class="btn btn-sm btn-link mb-1">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            @if ($logs->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Society</th>
                            <th>By</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ str($log->action)->headline() }}</td>
                                <td class="text-muted small">{{ str($log->module)->headline() }}</td>
                                <td>{{ $log->society?->name ?? '—' }}</td>
                                <td>{{ $log->superAdmin?->name ?? '—' }}</td>
                                <td>
                                    @php $changes = $log->getChangeSummary(); @endphp
                                    @if (!empty($changes))
                                        <button type="button" class="btn btn-xs btn-outline-secondary" data-toggle="collapse" data-target="#changes-{{ $log->id }}">
                                            {{ count($changes) }} field(s) changed
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
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $log->ip_address ?? '—' }}</td>
                                <td title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">No audit log entries found.</div>
            @endif
        </div>
    </div>

    @if ($logs->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $logs->links() }}
        </div>
    @endif
@stop
