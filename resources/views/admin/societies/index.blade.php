@extends('adminlte::page')

@section('title', 'Societies')

@php
    $statusTone = ['active' => 'success', 'inactive' => 'neutral', 'expired' => 'danger', 'archived' => 'neutral'];
    $dbTone = ['active' => 'success', 'creating' => 'info', 'created' => 'info', 'failed' => 'danger'];
    $statusTabs = ['' => ['All', $statusCounts->sum()]];
    foreach (['active', 'inactive', 'expired', 'archived'] as $status) {
        $statusTabs[$status] = [ucfirst($status), $statusCounts->get($status, 0)];
    }
@endphp

@section('content_header')
    <div class="fc-page-head">
        <h1 class="mb-0">Societies</h1>
        <a href="{{ route('admin.societies.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Society
        </a>
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
        <x-fc.tabs name="status" label="Society status" :tabs="$statusTabs" />

        <x-fc.toolbar placeholder="Search name, email, city…" :keep="['status']" />

        @if ($societies->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="name">Society</x-fc.th>
                            <x-fc.th sort="city">City</x-fc.th>
                            <th>Status</th>
                            <th>Database</th>
                            <x-fc.th sort="access" first="desc">Access Period</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($societies as $society)
                            @php
                                $dbStatus = $society->database->status ?? 'not_created';
                                $daysLeft = $society->end_date ? (int) now()->startOfDay()->diffInDays($society->end_date, false) : null;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$society->name" />
                                        <div>
                                            <a href="{{ route('admin.societies.show', $society->id) }}" class="fc-strong">{{ $society->name }}</a>
                                            @if ($society->is_trial)
                                                <x-fc.pill tone="purple" :dot="false" label="Trial" class="ml-1" />
                                            @endif
                                            <span class="fc-sub">{{ $society->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $society->city }}
                                    <span class="fc-sub">{{ $society->state }}</span>
                                </td>
                                <td><x-fc.pill :status="$society->status" :tone="$statusTone[$society->status] ?? 'neutral'" /></td>
                                <td><x-fc.pill :status="$dbStatus" :tone="$dbTone[$dbStatus] ?? 'neutral'" :dot="false" /></td>
                                <td class="fc-nowrap">
                                    {{ $society->start_date->format('d M Y') }} &ndash; {{ $society->end_date->format('d M Y') }}
                                    @if ($daysLeft !== null && $society->status === 'active')
                                        <span class="fc-sub {{ $daysLeft < 0 ? 'text-danger' : ($daysLeft <= 30 ? 'text-warning' : '') }}">
                                            {{ $daysLeft < 0 ? 'Ended '.abs($daysLeft).' days ago' : $daysLeft.' days left' }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="eye" label="View society" :href="route('admin.societies.show', $society->id)" />
                                        <x-fc.action icon="building" label="Blocks & flats" :href="route('admin.societies.blocks.index', $society->id)" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="buildings" title="No societies yet" :keep="['status']">
                <a href="{{ route('admin.societies.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create First Society</a>
            </x-fc.empty>
        @endif

        <x-fc.footer :paginator="$societies" />
    </div>
@stop
