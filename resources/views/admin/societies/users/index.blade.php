@extends('adminlte::page')

@section('title', 'Residents')

@section('content_header')
    <div class="fc-page-head">
        <h1 class="mb-0">{{ $society->name }} - Residents</h1>
        <div class="fc-page-actions">
            <a href="{{ route('admin.societies.show', $society->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="{{ route('admin.societies.users.create', $society->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Resident
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

    @php $total = $grouped->sum(fn ($users) => $users->count()); @endphp

    <div class="fc-card">
        <x-fc.toolbar placeholder="Search name, phone, email or flat…" :count="$total.' '.\Illuminate\Support\Str::plural('resident', $total)" />

        @if ($total > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Flat</th>
                            <th>Phone</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grouped as $blockName => $blockUsers)
                            <tr class="fc-group">
                                <td colspan="4"><i class="bi bi-building mr-1"></i> {{ $blockName }} &middot; {{ $blockUsers->count() }}</td>
                            </tr>
                            @foreach ($blockUsers as $user)
                                @php
                                    $residency = $user->residencies->firstWhere('is_primary', true) ?? $user->residencies->first();
                                    $email = str_ends_with($user->email, '@placeholder.flatcare.local') ? null : $user->email;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fc-person">
                                            <x-fc.avatar :name="$user->name" :round="true" />
                                            <div>
                                                <span class="fc-strong">{{ $user->name }}</span>
                                                @if ($email)
                                                    <span class="fc-sub">{{ $email }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fc-nowrap">{!! $residency?->flat ? '<span class="fc-strong">'.e($residency->flat->flat_number).'</span>' : '<span class="fc-dim">—</span>' !!}</td>
                                    <td class="fc-nowrap">{!! $user->phone ? e($user->phone) : '<span class="fc-dim">—</span>' !!}</td>
                                    <td>
                                        <div class="fc-actions">
                                            <x-fc.action icon="pencil" label="Edit resident" :href="route('admin.societies.users.edit', [$society->id, $user->id])" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="people" title="No registered residents yet">
                <a href="{{ route('admin.societies.users.create', $society->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Resident</a>
            </x-fc.empty>
        @endif
    </div>
@stop
