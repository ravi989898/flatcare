@extends('adminlte::page')

@section('title', 'Society Security')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Security</h1>
        </div>
        <div class="col-sm-6 text-right">
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

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-sun text-warning mr-3" style="font-size:1.75rem;"></i>
                    <div>
                        <div class="text-muted small">Day Shift — On Duty</div>
                        <div class="font-weight-bold">{{ $onDuty->get('day')?->name ?? 'Vacant' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-moon text-info mr-3" style="font-size:1.75rem;"></i>
                    <div>
                        <div class="text-muted small">Night Shift — On Duty</div>
                        <div class="font-weight-bold">{{ $onDuty->get('night')?->name ?? 'Vacant' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Security Guards</h3>
        </div>
        <div class="card-body p-0">
            @if ($guards->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($guards as $guard)
                            <tr>
                                <td>
                                    @if ($guard->photo_path)
                                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($guard->photo_path) }}" alt="{{ $guard->name }}" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                    @else
                                        <i class="fas fa-user-shield text-muted" style="font-size:1.5rem;"></i>
                                    @endif
                                </td>
                                <td>
                                    {{ $guard->name }}
                                    @if ($onDuty->get($guard->shift)?->id === $guard->id)
                                        <span class="badge badge-info">On duty</span>
                                    @endif
                                </td>
                                <td>{{ $guard->phone }}</td>
                                <td>{{ $guard->shift ? ucfirst($guard->shift) : '—' }}</td>
                                <td>
                                    @if ($guard->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $guard->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.societies.security.edit', [$society->id, $guard->id]) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    @if ($guard->status === 'active')
                                        <form action="{{ route('admin.societies.security.deactivate', [$society->id, $guard->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this guard?')">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.societies.security.activate', [$society->id, $guard->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Activate this guard?')">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">
                    <p>No security guards found for this society.</p>
                    <a href="{{ route('admin.societies.security.create', $society->id) }}" class="btn btn-primary">
                        Add First Security Guard
                    </a>
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
