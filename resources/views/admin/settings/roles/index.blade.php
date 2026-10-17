@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <div class="fc-page-head">
        <h1 class="mb-0">Roles</h1>
        <div class="fc-page-actions">
            <form action="{{ route('admin.settings.roles.sync') }}" method="POST" class="d-inline" data-confirm="Push the current role catalog into every provisioned society? Existing role assignments are kept.">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sync"></i> Sync to Societies
                </button>
            </form>
            <a href="{{ route('admin.settings.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Role
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
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <div class="fc-card">
        <div class="table-responsive">
            <table class="table fc-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th class="num">Priority</th>
                        <th>Type</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                <span class="fc-strong">{{ $role->display_name }}</span>
                                <span class="fc-sub"><code class="text-muted">{{ $role->name }}</code></span>
                            </td>
                            <td class="text-muted small">{{ $role->description }}</td>
                            <td class="num">{{ $role->priority }}</td>
                            <td>
                                @if ($role->is_system_role)
                                    <x-fc.pill tone="info" :dot="false" label="System" />
                                @else
                                    <x-fc.pill tone="neutral" :dot="false" label="Custom" />
                                @endif
                            </td>
                            <td>
                                <div class="fc-actions">
                                    <x-fc.action icon="pencil" label="Edit role" :href="route('admin.settings.roles.edit', $role->id)" />
                                    @unless ($role->is_system_role)
                                        <x-fc.post-action :action="route('admin.settings.roles.destroy', $role->id)" method="DELETE"
                                            icon="trash" label="Delete role" tone="danger" confirm="Delete this role?" />
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="fc-footer small">
            <span>
                This is the role template new societies are provisioned with. Editing a role here does not change
                an already-provisioned society until you click "Sync to Societies" — existing role assignments and
                permissions are never removed by a sync. To control which sidebar menus each role sees, go to
                <a href="{{ route('admin.settings.menu.edit') }}">Menu Settings</a>.
            </span>
        </div>
    </div>
@stop
