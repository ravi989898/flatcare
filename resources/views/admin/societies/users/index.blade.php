@extends('adminlte::page')

@section('title', 'Residents')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>{{ $society->name }} - Residents</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.societies.users.create', $society->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Resident
            </a>
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
                            <th style="width: 100px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blockUsers as $user)
                            @php
                                $residency = $user->residencies->firstWhere('is_primary', true) ?? $user->residencies->first();
                            @endphp
                            <tr>
                                <td>{{ $residency?->flat?->flat_number ?? '—' }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ str_ends_with($user->email, '@placeholder.flatcare.local') ? '—' : $user->email }}</td>
                                <td>{{ $user->phone }}</td>
                                <td>
                                    <a href="{{ route('admin.societies.users.edit', [$society->id, $user->id]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
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
