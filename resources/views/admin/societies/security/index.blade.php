@extends('adminlte::page')

@section('title', 'Security')

@section('content_header')
    <div class="fc-page-head">
        <h1 class="mb-0">{{ $society->name }} - Security</h1>
        <div class="fc-page-actions">
            <a href="{{ route('admin.societies.security.history', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-history"></i> Duty History
            </a>
            <a href="{{ route('admin.societies.security.create', $society->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Security Guard
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="fc-stats">
        <x-fc.stat icon="sun" tone="warning" :value="$onDuty->get('day')?->name ?? 'Vacant'" label="Day shift — on duty" />
        <x-fc.stat icon="moon-stars" tone="info" :value="$onDuty->get('night')?->name ?? 'Vacant'" label="Night shift — on duty" />
    </div>

    <div class="fc-card">
        <x-fc.toolbar placeholder="Search name or phone…">
            <x-fc.select name="shift" placeholder="Any shift" :options="['day' => 'Day', 'night' => 'Night']" />
            <x-fc.select name="status" placeholder="Any status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
        </x-fc.toolbar>

        @if ($guards->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <x-fc.th sort="name">Guard</x-fc.th>
                            <th>Phone</th>
                            <x-fc.th sort="shift">Shift</x-fc.th>
                            <th>Status</th>
                            <x-fc.th sort="added" :default="true" first="desc">Added</x-fc.th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($guards as $guard)
                            @php $isOnDuty = $guard->shift && $onDuty->get($guard->shift)?->id === $guard->id; @endphp
                            <tr>
                                <td>
                                    <div class="fc-person">
                                        <x-fc.avatar :name="$guard->name" :round="true"
                                            :src="$guard->photo_path ? Illuminate\Support\Facades\Storage::disk('public')->url($guard->photo_path) : null" />
                                        <div>
                                            <span class="fc-strong">{{ $guard->name }}</span>
                                            @if ($isOnDuty)
                                                <span class="fc-sub text-success"><i class="bi bi-broadcast"></i> On duty now</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="fc-nowrap">{{ $guard->phone }}</td>
                                <td>
                                    @if ($guard->shift)
                                        <span class="fc-nowrap"><i class="bi bi-{{ $guard->shift === 'night' ? 'moon-stars' : 'sun' }} text-muted mr-1"></i>{{ ucfirst($guard->shift) }}</span>
                                    @else
                                        <span class="fc-dim">—</span>
                                    @endif
                                </td>
                                <td><x-fc.pill :status="$guard->status" /></td>
                                <td class="fc-nowrap">{{ $guard->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="fc-actions">
                                        <x-fc.action icon="pencil" label="Edit guard" :href="route('admin.societies.security.edit', [$society->id, $guard->id])" />
                                        @if ($guard->status === 'active')
                                            <x-fc.post-action :action="route('admin.societies.security.deactivate', [$society->id, $guard->id])"
                                                icon="pause-circle" label="Deactivate" tone="warning" confirm="Deactivate this guard?" />
                                        @else
                                            <x-fc.post-action :action="route('admin.societies.security.activate', [$society->id, $guard->id])"
                                                icon="play-circle" label="Activate" confirm="Activate this guard?" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="shield-lock" title="No security guards yet">
                <a href="{{ route('admin.societies.security.create', $society->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add the first security guard</a>
            </x-fc.empty>
        @endif

        <x-fc.footer :paginator="$guards" />
    </div>
@stop
