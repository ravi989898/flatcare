@extends('society.layout')

@section('title', 'Emergency Contacts')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Emergency Contacts</h1>
        <p class="text-muted mb-0">Shown as tap-to-call tiles (Security, Ambulance, Fire Brigade, Police, ...) in the resident app.</p>
    </div>

    <div class="card stat-card mb-3">
        <div class="card-header bg-white"><strong>Add a Contact</strong></div>
        <div class="card-body">
            <form action="{{ route('society.emergency-contacts.store') }}" method="POST" class="row g-2 align-items-end" novalidate>
                @csrf
                <div class="col-md-3">
                    <label class="form-label small text-muted">Label</label>
                    <input type="text" name="label" class="form-control" placeholder="e.g. Security" maxlength="100" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Type</label>
                    <input type="text" name="type" class="form-control" placeholder="e.g. security" maxlength="50" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Phone</label>
                    <input type="text" name="phone" class="form-control" maxlength="20" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Availability</label>
                    <input type="text" name="availability" class="form-control" placeholder="24x7 Available" maxlength="100">
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted">Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-brand w-100" aria-label="Add contact"><i class="bi bi-plus-lg"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="fc-card">
        @if ($contacts->count() > 0)
            <div class="table-responsive">
                <table class="table fc-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Phone</th>
                            <th>Availability</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contacts as $contact)
                            {{-- Inputs reference this form via the "form" attribute rather than
                                 nesting a <form> inside <tr>/<td>, which browsers handle
                                 inconsistently. --}}
                            <form id="edit-contact-{{ $contact->id }}" action="{{ route('society.emergency-contacts.update', $contact->id) }}" method="POST" novalidate></form>
                            <tr>
                                <td><input form="edit-contact-{{ $contact->id }}" type="text" name="label" class="form-control form-control-sm" value="{{ $contact->label }}" maxlength="100" aria-label="Label"></td>
                                <td><input form="edit-contact-{{ $contact->id }}" type="text" name="type" class="form-control form-control-sm" value="{{ $contact->type }}" maxlength="50" aria-label="Type"></td>
                                <td><input form="edit-contact-{{ $contact->id }}" type="text" name="phone" class="form-control form-control-sm" value="{{ $contact->phone }}" maxlength="20" aria-label="Phone"></td>
                                <td><input form="edit-contact-{{ $contact->id }}" type="text" name="availability" class="form-control form-control-sm" value="{{ $contact->availability }}" maxlength="100" aria-label="Availability"></td>
                                <td>
                                    <input form="edit-contact-{{ $contact->id }}" type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input form="edit-contact-{{ $contact->id }}" type="hidden" name="_method" value="PUT">
                                    <input form="edit-contact-{{ $contact->id }}" type="hidden" name="sort_order" value="{{ $contact->sort_order }}">
                                    <div class="fc-actions">
                                        <x-fc.action type="submit" form="edit-contact-{{ $contact->id }}" icon="check-lg" label="Save changes" />
                                        <x-fc.post-action :action="route('society.emergency-contacts.destroy', $contact->id)" method="DELETE"
                                            icon="trash" label="Remove" tone="danger" confirm="Remove this contact?" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-fc.empty icon="telephone" title="No emergency contacts yet" text="Add Security, Ambulance, Police and other numbers with the form above." />
        @endif

        <x-fc.footer :paginator="$contacts" noun="contacts" />
    </div>
@stop
