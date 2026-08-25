@extends('society.layout')

@section('title', 'Service Providers')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Service Providers</h1>
        <p class="text-muted mb-0">Vetted plumbers, electricians, carpenters etc. residents can browse and call directly.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card mb-3">
        <div class="card-header bg-white"><strong>Add a Service Provider</strong></div>
        <div class="card-body">
            <form action="{{ route('society.service-providers.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small text-muted">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Service Type</label>
                    <input type="text" name="service_type" class="form-control" placeholder="e.g. Plumber" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Available 9am-6pm">
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted">Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-brand w-100"><i class="bi bi-plus-lg"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($providers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Service Type</th>
                                <th>Phone</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($providers as $provider)
                                <form id="edit-provider-{{ $provider->id }}" action="{{ route('society.service-providers.update', $provider->id) }}" method="POST"></form>
                                <tr>
                                    <td><input form="edit-provider-{{ $provider->id }}" type="text" name="name" class="form-control form-control-sm" value="{{ $provider->name }}"></td>
                                    <td><input form="edit-provider-{{ $provider->id }}" type="text" name="service_type" class="form-control form-control-sm" value="{{ $provider->service_type }}"></td>
                                    <td><input form="edit-provider-{{ $provider->id }}" type="text" name="phone" class="form-control form-control-sm" value="{{ $provider->phone }}"></td>
                                    <td><input form="edit-provider-{{ $provider->id }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $provider->notes }}"></td>
                                    <td class="text-end text-nowrap">
                                        @csrf
                                        @method('PUT')
                                        <input form="edit-provider-{{ $provider->id }}" type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input form="edit-provider-{{ $provider->id }}" type="hidden" name="_method" value="PUT">
                                        <input form="edit-provider-{{ $provider->id }}" type="hidden" name="sort_order" value="{{ $provider->sort_order }}">
                                        <button form="edit-provider-{{ $provider->id }}" type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check-lg"></i></button>
                                        <form action="{{ route('society.service-providers.destroy', $provider->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this provider?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-tools fs-1 d-block mb-2"></i>
                    No service providers added yet.
                </div>
            @endif
        </div>
    </div>
@stop
