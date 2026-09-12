@extends('adminlte::page')

@section('title', 'Security Duty History')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Security Duty History</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.security.index', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Security
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Duty Periods</h3>
            <div class="card-tools">
                <small class="text-muted">Who was on Day/Night duty, and when.</small>
            </div>
        </div>
        <div class="card-body p-0">
            @if ($logs->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Guard</th>
                            <th>Shift</th>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ $log->securityGuard->name ?? '—' }}</td>
                                <td>{{ ucfirst($log->shift) }}</td>
                                <td>{{ $log->started_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    @if ($log->ended_at)
                                        {{ $log->ended_at->format('d M Y, h:i A') }}
                                    @else
                                        <span class="badge badge-success">Ongoing</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">
                    <p>No duty history yet.</p>
                </div>
            @endif
        </div>
    </div>

    @if ($logs->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $logs->links() }}
        </div>
    @endif
@stop
