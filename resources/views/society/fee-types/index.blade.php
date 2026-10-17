@extends('society.layout')

@section('title', 'Fee Types')

@section('content')
    <div class="fc-page-head mb-4">
        <div>
            <h1 class="h3 mb-1">Fee Types</h1>
            <p class="text-muted mb-0">One-off charge types available when raising an Extra Charge.</p>
        </div>
        <a href="{{ route('society.extra-charges.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Extra Charges
        </a>
    </div>

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

    <div class="fc-card">
        @if ($feeTypes->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Default Amount</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($feeTypes as $feeType)
                            <form id="edit-fee-type-{{ $feeType->id }}" action="{{ route('society.fee-types.update', $feeType->id) }}" method="POST" novalidate></form>
                            <tr>
                                <td><input form="edit-fee-type-{{ $feeType->id }}" type="text" name="name" class="form-control form-control-sm" value="{{ $feeType->name }}" maxlength="100" aria-label="Name"></td>
                                <td style="max-width: 220px;">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                        <input form="edit-fee-type-{{ $feeType->id }}" type="number" name="default_amount" step="0.01" min="0" class="form-control" value="{{ $feeType->default_amount }}" aria-label="Default amount">
                                    </div>
                                </td>
                                <td>
                                    <input form="edit-fee-type-{{ $feeType->id }}" type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input form="edit-fee-type-{{ $feeType->id }}" type="hidden" name="_method" value="PUT">
                                    <input form="edit-fee-type-{{ $feeType->id }}" type="hidden" name="sort_order" value="{{ $feeType->sort_order }}">
                                    <div class="fc-actions">
                                        <x-fc.action type="submit" form="edit-fee-type-{{ $feeType->id }}" icon="check-lg" label="Save changes" />
                                        <x-fc.post-action :action="route('society.fee-types.destroy', $feeType->id)" method="DELETE"
                                            icon="trash" label="Remove" tone="danger" confirm="Remove this fee type?" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="tag" title="No fee types yet" text="Add the one-off charges your society raises, like event or penalty charges." />
        @endif

        <x-fc.footer :paginator="$feeTypes" noun="fee types" />
    </div>
@stop
