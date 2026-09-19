@extends('society.layout')

@section('title', 'Security')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h3 mb-1">Security</h1>
            <p class="text-muted mb-0">Day/night duty roster and history</p>
        </div>
        <div>
            <a href="{{ route('society.security.history') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history"></i> Duty History
            </a>
            <a href="{{ route('society.security.create') }}" class="btn btn-brand">
                <i class="bi bi-plus-lg"></i> Add Security Guard
            </a>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-sun"></i></div>
                    <div>
                        <div class="text-muted small">Day Shift — On Duty</div>
                        <div class="fw-semibold">{{ $onDuty->get('day')?->name ?? 'Vacant' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-moon-stars"></i></div>
                    <div>
                        <div class="text-muted small">Night Shift — On Duty</div>
                        <div class="fw-semibold">{{ $onDuty->get('night')?->name ?? 'Vacant' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($guards->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Shift</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($guards as $guard)
                                <tr>
                                    <td>
                                        @if ($guard->photo_path)
                                            <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($guard->photo_path) }}" alt="{{ $guard->name }}" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                        @else
                                            <i class="bi bi-shield-lock text-muted fs-4"></i>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $guard->name }}
                                        @if ($onDuty->get($guard->shift)?->id === $guard->id)
                                            <span class="badge bg-info-subtle text-info-emphasis">On duty</span>
                                        @endif
                                    </td>
                                    <td>{{ $guard->phone }}</td>
                                    <td>{{ $guard->shift ? ucfirst($guard->shift) : '—' }}</td>
                                    <td>
                                        @if ($guard->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $guard->created_at->format('d M Y') }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('society.security.edit', $guard->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            @if ($guard->status === 'active')
                                                <form action="{{ route('society.security.deactivate', $guard->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="Deactivate this guard?">Deactivate</button>
                                                </form>
                                            @else
                                                <form action="{{ route('society.security.activate', $guard->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" data-confirm="Activate this guard?">Activate</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-shield-lock fs-1 d-block mb-2"></i>
                    <p class="mb-2">No security guards found.</p>
                    <a href="{{ route('society.security.create') }}" class="btn btn-brand btn-sm">Add the first security guard</a>
                </div>
            @endif
        </div>
    </div>

    @if ($guards->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $guards->links() }}
        </div>
    @endif
@stop
