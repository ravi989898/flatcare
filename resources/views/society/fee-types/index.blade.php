@extends('society.layout')

@section('title', 'Fee Types')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1">Fee Types</h1>
            <p class="text-muted mb-0">One-off charge types available when raising an Extra Charge.</p>
        </div>
        <a href="{{ route('society.extra-charges.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Extra Charges
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card mb-3">
        <div class="card-header bg-white"><strong>Add a Fee Type</strong></div>
        <div class="card-body">
            <form action="{{ route('society.fee-types.store') }}" method="POST" class="row align-items-end" novalidate>
                @csrf
                <div class="col-md-5 mb-2">
                    <label class="font-weight-bold mb-1 d-block small text-muted">Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Function/Event Charge" maxlength="100" required>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="font-weight-bold mb-1 d-block small text-muted">Default Amount</label>
                    <input type="number" name="default_amount" step="0.01" min="0" class="form-control" placeholder="Optional">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="font-weight-bold mb-1 d-block small text-muted">Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control" value="0">
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-brand w-100"><i class="bi bi-plus-lg"></i> Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            @if ($feeTypes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Default Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($feeTypes as $feeType)
                                <form id="edit-fee-type-{{ $feeType->id }}" action="{{ route('society.fee-types.update', $feeType->id) }}" method="POST" novalidate></form>
                                <tr>
                                    <td><input form="edit-fee-type-{{ $feeType->id }}" type="text" name="name" class="form-control form-control-sm" value="{{ $feeType->name }}" maxlength="100"></td>
                                    <td><input form="edit-fee-type-{{ $feeType->id }}" type="number" name="default_amount" step="0.01" min="0" class="form-control form-control-sm" value="{{ $feeType->default_amount }}"></td>
                                    <td class="text-right text-nowrap">
                                        @csrf
                                        @method('PUT')
                                        <input form="edit-fee-type-{{ $feeType->id }}" type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input form="edit-fee-type-{{ $feeType->id }}" type="hidden" name="_method" value="PUT">
                                        <input form="edit-fee-type-{{ $feeType->id }}" type="hidden" name="sort_order" value="{{ $feeType->sort_order }}">
                                        <button form="edit-fee-type-{{ $feeType->id }}" type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check-lg"></i></button>
                                        <form action="{{ route('society.fee-types.destroy', $feeType->id) }}" method="POST" class="d-inline" data-confirm="Remove this fee type?">
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
                    <i class="bi bi-tag fs-1 d-block mb-2"></i>
                    No fee types added yet.
                </div>
            @endif
        </div>
    </div>
@stop
