@extends('adminlte::page')

@section('title', 'Dashboard Widgets')

@section('content_header')
    <h1>Dashboard Widgets</h1>
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Super Admin Dashboard — Visibility by Role</h3>
        </div>
        <form action="{{ route('admin.settings.dashboard_widgets.update') }}" method="POST">
            @csrf
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Widget</th>
                                @foreach ($roles as $role)
                                    <th class="text-center">{{ $role->display_name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($widgets as $widget)
                                <tr>
                                    <td>{{ $widget->label }}</td>
                                    @foreach ($roles as $role)
                                        <td class="text-center">
                                            <input type="checkbox"
                                                name="visibility[{{ $role->id }}][{{ $widget->id }}]"
                                                value="1"
                                                {{ ($visibility[$role->id][$widget->id] ?? false) ? 'checked' : '' }}>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Visibility</button>
                <span class="text-muted small ml-2">
                    Only Super Admin (and, in future, any other admin-level role) can actually reach
                    <code>/admin/dashboard</code> — the other roles' columns here are inert until such a role gains
                    admin-panel access.
                </span>
            </div>
        </form>
    </div>
@stop
