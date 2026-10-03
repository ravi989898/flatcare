@extends('society.layout')

@section('title', 'App Permission')

@section('content_header')
    <div>
        <h1 class="h3 mb-1">App Permission</h1>
        <p class="text-muted mb-0">Choose which mobile app menu items Treasurer, Vice Chairman, Secretary, Committee Member and Resident can see in this society.</p>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">App Menu Visibility by Role</h3>
        </div>
        <form action="{{ route('society.app-permissions.update') }}" method="POST" novalidate>
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
                            @foreach ($menuItems->groupBy('group_label') as $groupLabel => $groupItems)
                                <tr class="bg-light">
                                    <td colspan="{{ $roles->count() + 1 }}"><strong>{{ $groupLabel }}</strong></td>
                                </tr>
                                @foreach ($groupItems as $item)
                                    <tr>
                                        <td>
                                            @if ($item->icon)
                                                <span class="mr-1">{{ $item->icon }}</span>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-brand"><i class="fas fa-save"></i> Save Permissions</button>
                <span class="text-muted small ml-2">
                    Only affects this society's mobile app menu. Super Admin and Society Admin always see every item.
                </span>
            </div>
        </form>
    </div>
@stop
