@extends('adminlte::page')

@section('title', 'Inquiries')

@section('content_header')
    <h1>Trial Inquiries</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form action="{{ route('admin.inquiries.index') }}" method="GET" class="form-inline">
                <input type="text" name="search" class="form-control form-control-sm mr-2 mb-1" placeholder="Search society, name, email, phone" value="{{ request('search') }}">
                <select name="status" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">All Statuses</option>
                    @foreach (['new' => 'New', 'contacted' => 'Contacted', 'dismissed' => 'Dismissed'] as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-secondary mb-1"><i class="fas fa-search"></i> Filter</button>
                @if (request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.inquiries.index') }}" class="btn btn-sm btn-link mb-1">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            @if ($inquiries->count() > 0)
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Society</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Received</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inquiries as $inquiry)
                            <tr>
                                <td class="fw-bold">{{ $inquiry->society_name }}</td>
                                <td>
                                    <div>{{ $inquiry->contact_name }}</div>
                                    <div class="small text-muted">
                                        <a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a>
                                        &middot;
                                        <a href="tel:{{ $inquiry->phone }}">{{ $inquiry->phone }}</a>
                                    </div>
                                </td>
                                <td class="small text-muted">{{ Str::limit($inquiry->address, 60) }}</td>
                                <td>
                                    @switch($inquiry->status)
                                        @case('new')
                                            <span class="badge badge-danger">New</span>
                                            @break
                                        @case('contacted')
                                            <span class="badge badge-success">Contacted</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ ucfirst($inquiry->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="small text-muted" title="{{ $inquiry->created_at }}">{{ $inquiry->created_at->diffForHumans() }}</td>
                                <td class="text-right text-nowrap">
                                    @if ($inquiry->status === 'new')
                                        <form action="{{ route('admin.inquiries.mark_contacted', $inquiry) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-outline-success"><i class="fas fa-check"></i> Mark contacted</button>
                                        </form>
                                        <form action="{{ route('admin.inquiries.dismiss', $inquiry) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-outline-secondary"><i class="fas fa-times"></i> Dismiss</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.inquiries.destroy', $inquiry) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this inquiry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-4 text-center text-muted">No trial inquiries yet.</div>
            @endif
        </div>
    </div>

    @if ($inquiries->count() > 0)
        <div class="d-flex justify-content-center mt-3">
            {{ $inquiries->links() }}
        </div>
    @endif
@stop
