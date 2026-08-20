@extends('adminlte::page')

@section('title', 'Menu Settings')

@section('content_header')
    <h1>Menu Settings</h1>
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
            <h3 class="card-title">Society Portal Sidebar — Visibility by Role</h3>
        </div>
        <form action="{{ route('admin.settings.menu.update') }}" method="POST">
            @csrf
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Menu Item</th>
                                @foreach ($roles as $role)
                                    <th class="text-center">{{ $role->display_name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menuItems as $item)
                                <tr>
                                    <td>
                                        @if ($item->icon)
                                            <i class="bi {{ $item->icon }} mr-1"></i>
                                        @endif
                                        {{ $item->label }}
                                    </td>
                                    @foreach ($roles as $role)
                                        <td class="text-center">
                                            <input type="checkbox"
                                                name="visibility[{{ $role->id }}][{{ $item->id }}]"
                                                value="1"
                                                {{ ($visibility[$role->id][$item->id] ?? false) ? 'checked' : '' }}>
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
                    Controls which sidebar links show for each role in the society portal. It does not change what
                    those roles are permitted to <em>do</em> — permissions are managed per society.
                </span>
            </div>
        </form>
    </div>
@stop
