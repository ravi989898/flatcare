@extends('adminlte::page')

@section('title', 'Security Duty History')

@section('content_header')
    <div class="fc-page-head">
        <div>
            <h1 class="mb-0">{{ $society->name }} - Security Duty History</h1>
            <p class="text-muted mb-0">Who was on Day/Night duty, and when.</p>
        </div>
        <a href="{{ route('admin.societies.security.index', $society->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Security
        </a>
    </div>
@stop

@section('content')
    <div class="fc-card">
        <x-fc.toolbar placeholder="Search guard name or phone…">
            <x-fc.select name="shift" placeholder="Any shift" :options="['day' => 'Day', 'night' => 'Night']" />
        </x-fc.toolbar>

        @if ($logs->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <th>Guard</th>
                            <th>Shift</th>
                            <x-fc.th sort="started" :default="true" first="desc">From</x-fc.th>
                            <x-fc.th sort="ended" first="desc">To</x-fc.th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$log->securityGuard->name ?? '?'" :round="true" />
                                        <span class="fc-strong">{{ $log->securityGuard->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="fc-nowrap"><i class="bi bi-{{ $log->shift === 'night' ? 'moon-stars' : 'sun' }} text-muted mr-1"></i>{{ ucfirst($log->shift) }}</td>
                                <td class="fc-nowrap">{{ $log->started_at->format('d M Y, h:i A') }}</td>
                                <td class="fc-nowrap">
                                    @if ($log->ended_at)
                                        {{ $log->ended_at->format('d M Y, h:i A') }}
                                    @else
                                        <x-fc.pill status="ongoing" tone="success" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="clock-history" title="No duty history yet" text="Shift changes are recorded here automatically." />
        @endif

        <x-fc.footer :paginator="$logs" />
    </div>
@stop
