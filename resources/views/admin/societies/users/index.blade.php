@extends('adminlte::page')

@section('title', 'Residents')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Residents</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    @forelse ($grouped as $blockName => $blockUsers)
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $blockName }}</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Flat No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th style="width: 260px;">Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blockUsers as $user)
                            @php
                                $residency = $user->residencies->firstWhere('is_primary', true) ?? $user->residencies->first();
                                $currentRoleId = $user->roles->first()?->id;
                            @endphp
                            <tr>
                                <td>{{ $residency?->flat?->flat_number ?? '—' }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone }}</td>
                                <td>
                                    <form action="{{ route('admin.societies.users.role', [$society->id, $user->id]) }}" method="POST" class="form-inline">
                                        @csrf
                                        <select name="role_id" class="custom-select custom-select-sm mr-1" style="max-width: 170px;">
                                            <option value="">— No Role —</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}" {{ $currentRoleId === $role->id ? 'selected' : '' }}>
                                                    {{ $role->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body p-5 text-center text-muted">
                No registered users found for this society.
            </div>
        </div>
    @endforelse
@stop
