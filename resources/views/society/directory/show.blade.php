@extends('society.layout')

@section('title', $resident->name)

@php
    $residentTypeBadge = ['owner' => 'success', 'tenant' => 'info', 'occupant' => 'secondary'];
@endphp

@section('content')
    <div class="mb-4">
        <a href="{{ route('society.directory.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Directory
        </a>
        <h1 class="h3 mb-0 mt-2">{{ $resident->name }}</h1>
        <p class="text-muted mb-0">{{ $resident->phone }} &middot; {{ $resident->email }}</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card stat-card mb-3">
                <div class="card-header bg-white"><strong>Flat(s)</strong></div>
                <div class="card-body p-0">
                    @if ($resident->residencies->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach ($resident->residencies as $residency)
                                <li class="list-group-item d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-semibold">{{ $residency->flat?->display_label ?? '—' }}</div>
                                        <div class="text-muted small">
                                            Moved in {{ $residency->moved_in_date?->format('d M Y') ?? '—' }}
                                            @if ($residency->is_primary) &middot; Primary contact @endif
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $residentTypeBadge[$residency->resident_type] }}">{{ ucfirst($residency->resident_type) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted p-3 mb-0">Not linked to any flat.</p>
                    @endif
                </div>
            </div>

            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Status</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Account Status</dt>
                        <dd class="col-sm-8"><span class="badge bg-{{ $resident->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($resident->status) }}</span></dd>

                        <dt class="col-sm-4">Roles</dt>
                        <dd class="col-sm-8">{{ $resident->roles->pluck('display_name')->implode(', ') ?: 'No role assigned' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card stat-card mt-3">
                <div class="card-header bg-white"><strong>Committee Membership</strong></div>
                <div class="card-body">
                    <p class="text-muted small">Set a title (e.g. Chairman, Secretary, Treasurer) to list this resident under Committee Members in the mobile app. Leave blank to remove them from that list.</p>
                    <form action="{{ route('society.directory.committee.update', $resident->id) }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-sm-7">
                            <label class="form-label small text-muted">Position</label>
                            <input type="text" name="committee_position" class="form-control" placeholder="e.g. Chairman" value="{{ old('committee_position', $resident->committee_position) }}">
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label small text-muted">Order</label>
                            <input type="number" name="committee_order" min="0" class="form-control" value="{{ old('committee_order', $resident->committee_order) }}">
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-brand w-100">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card stat-card mb-3">
                <div class="card-header bg-white"><strong>Family Members</strong></div>
                <div class="card-body p-0">
                    @if ($resident->familyMembers->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach ($resident->familyMembers as $member)
                                <li class="list-group-item">
                                    <div class="fw-semibold">{{ $member->name }}</div>
                                    <div class="text-muted small">{{ ucfirst($member->relation) }}{{ $member->phone ? ' · ' . $member->phone : '' }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted p-3 mb-0">No family members added yet.</p>
                    @endif
                </div>
            </div>

            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Vehicles</strong></div>
                <div class="card-body p-0">
                    @if ($resident->vehicles->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach ($resident->vehicles as $vehicle)
                                <li class="list-group-item">
                                    <div class="fw-semibold">{{ $vehicle->registration_number }}</div>
                                    <div class="text-muted small">{{ ucfirst($vehicle->vehicle_type) }}{{ $vehicle->model ? ' · ' . $vehicle->model : '' }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted p-3 mb-0">No vehicles added yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
