@extends('society.layout')

@section('title', 'Emergency Contacts')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Emergency Contacts</h1>
        <p class="text-muted mb-0">Shown as tap-to-call tiles (Security, Ambulance, Fire Brigade, Police, ...) in the resident app.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card stat-card mb-3">
        <div class="card-header bg-white"><strong>Add a Contact</strong></div>
        <div class="card-body">
            <form action="{{ route('society.emergency-contacts.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small text-muted">Label</label>
                    <input type="text" name="label" class="form-control" placeholder="e.g. Security" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Type</label>
                    <input type="text" name="type" class="form-control" placeholder="e.g. security" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Availability</label>
                    <input type="text" name="availability" class="form-control" placeholder="24x7 Available">
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
            @if ($contacts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Type</th>
                                <th>Phone</th>
                                <th>Availability</th>
                                <th></th>
                            </tr>
                        </thead>
        <tbody>
                            @foreach ($contacts as $contact)
                                {{-- Inputs reference this form via the "form" attribute rather than
                                     nesting a <form> inside <tr>/<td>, which browsers handle
                                     inconsistently. --}}
                                <form id="edit-contact-{{ $contact->id }}" action="{{ route('society.emergency-contacts.update', $contact->id) }}" method="POST"></form>
                                <tr>
                                    <td><input form="edit-contact-{{ $contact->id }}" type="text" name="label" class="form-control form-control-sm" value="{{ $contact->label }}"></td>
                                    <td><input form="edit-contact-{{ $contact->id }}" type="text" name="type" class="form-control form-control-sm" value="{{ $contact->type }}"></td>
                                    <td><input form="edit-contact-{{ $contact->id }}" type="text" name="phone" class="form-control form-control-sm" value="{{ $contact->phone }}"></td>
                                    <td><input form="edit-contact-{{ $contact->id }}" type="text" name="availability" class="form-control form-control-sm" value="{{ $contact->availability }}"></td>
                                    <td class="text-end text-nowrap">
                                        @csrf
                                        @method('PUT')
                                        <input form="edit-contact-{{ $contact->id }}" type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input form="edit-contact-{{ $contact->id }}" type="hidden" name="_method" value="PUT">
                                        <input form="edit-contact-{{ $contact->id }}" type="hidden" name="sort_order" value="{{ $contact->sort_order }}">
                                        <button form="edit-contact-{{ $contact->id }}" type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check-lg"></i></button>
                                        <form action="{{ route('society.emergency-contacts.destroy', $contact->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this contact?');">
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
                    <i class="bi bi-telephone fs-1 d-block mb-2"></i>
                    No emergency contacts added yet.
                </div>
            @endif
        </div>
    </div>
@stop
