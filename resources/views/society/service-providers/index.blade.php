@extends('society.layout')

@section('title', 'Service Providers')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Service Providers</h1>
        <p class="text-muted mb-0">Vetted plumbers, electricians, carpenters etc. residents can browse and call directly.</p>
    </div>

    <div class="card stat-card mb-3">
        <div class="card-header bg-white"><strong>Add a Service Provider</strong></div>
        <div class="card-body">
            <form action="{{ route('society.service-providers.store') }}" method="POST" class="row g-2 align-items-end" novalidate>
                @csrf
                <div class="col-md-3">
                    <label class="form-label small text-muted">Name</label>
                    <input type="text" name="name" class="form-control" maxlength="100" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Service Type</label>
                    <input type="text" name="service_type" class="form-control" placeholder="e.g. Plumber" maxlength="100" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Phone</label>
                    <input type="text" name="phone" class="form-control" maxlength="20" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Available 9am-6pm" maxlength="255">
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted">Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-brand w-100" aria-label="Add service provider"><i class="bi bi-plus-lg"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="fc-card">
        @if ($providers->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Service Type</th>
                            <th>Phone</th>
                            <th>Notes</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($providers as $provider)
                            <form id="edit-provider-{{ $provider->id }}" action="{{ route('society.service-providers.update', $provider->id) }}" method="POST" novalidate></form>
                            <tr>
                                <td><input form="edit-provider-{{ $provider->id }}" type="text" name="name" class="form-control form-control-sm" value="{{ $provider->name }}" maxlength="100" aria-label="Name"></td>
                                <td><input form="edit-provider-{{ $provider->id }}" type="text" name="service_type" class="form-control form-control-sm" value="{{ $provider->service_type }}" maxlength="100" aria-label="Service type"></td>
                                <td><input form="edit-provider-{{ $provider->id }}" type="text" name="phone" class="form-control form-control-sm" value="{{ $provider->phone }}" maxlength="20" aria-label="Phone"></td>
                                <td><input form="edit-provider-{{ $provider->id }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $provider->notes }}" maxlength="255" aria-label="Notes"></td>
                                <td>
                                    <input form="edit-provider-{{ $provider->id }}" type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input form="edit-provider-{{ $provider->id }}" type="hidden" name="_method" value="PUT">
                                    <input form="edit-provider-{{ $provider->id }}" type="hidden" name="sort_order" value="{{ $provider->sort_order }}">
                                    <div class="fc-actions">
                                        <x-fc.action type="submit" form="edit-provider-{{ $provider->id }}" icon="check-lg" label="Save changes" />
                                        <x-fc.post-action :action="route('society.service-providers.destroy', $provider->id)" method="DELETE"
                                            icon="trash" label="Remove" tone="danger" confirm="Remove this provider?" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="tools" title="No service providers yet" text="Add plumbers, electricians and others residents can call." />
        @endif

        <x-fc.footer :paginator="$providers" noun="providers" />
    </div>
@stop
