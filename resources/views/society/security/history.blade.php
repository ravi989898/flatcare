@extends('society.layout')

@section('title', 'Security Duty History')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Security Duty History</h1>
            <p class="text-muted mb-0">Who was on Day/Night duty, and when.</p>
        </div>
        <a href="{{ route('society.security.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Security
        </a>
    </div>
@stop

@section('content')
    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
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
                                            <span class="badge bg-success">Ongoing</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                    <p class="mb-0">No duty history yet.</p>
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
