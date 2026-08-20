@php
    $old = fn ($field, $default = null) => old($field, $society->{$field} ?? $default);
    $oldDate = fn ($field, $default) => old($field, $society?->{$field}?->format('Y-m-d') ?? $default);
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Society Details</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="name">Society Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ $old('name') }}" required>
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ $old('description') }}</textarea>
            @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ $old('email') }}" required>
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="phone">Phone <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ $old('phone') }}" required>
                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="alternate_phone">Alternate Phone</label>
                <input type="text" class="form-control @error('alternate_phone') is-invalid @enderror" id="alternate_phone" name="alternate_phone" value="{{ $old('alternate_phone') }}">
                @error('alternate_phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ $old('address') }}" required>
            @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="city">City <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ $old('city') }}" required>
                @error('city')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="state">State <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state" value="{{ $old('state') }}" required>
                @error('state')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="country">Country</label>
                <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ $old('country', 'India') }}">
                @error('country')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="postal_code">Postal Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ $old('postal_code') }}" required>
                @error('postal_code')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="registration_number">Registration Number</label>
                <input type="text" class="form-control @error('registration_number') is-invalid @enderror" id="registration_number" name="registration_number" value="{{ $old('registration_number') }}">
                @error('registration_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-4">
                <label for="total_flats">Total Flats</label>
                <input type="number" min="0" class="form-control @error('total_flats') is-invalid @enderror" id="total_flats" name="total_flats" value="{{ $old('total_flats') }}">
                @error('total_flats')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-4">
                <label for="total_blocks">Total Blocks</label>
                <input type="number" min="0" class="form-control @error('total_blocks') is-invalid @enderror" id="total_blocks" name="total_blocks" value="{{ $old('total_blocks') }}">
                @error('total_blocks')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        <hr>
        <h5>Maintenance Billing</h5>
        <p class="text-muted">Society admins enter each flat's monthly water reading; the bill is calculated as (units consumed &times; water unit rate) + fixed maintenance.</p>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="fixed_maintenance">Fixed Maintenance (₹)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('fixed_maintenance') is-invalid @enderror" id="fixed_maintenance" name="fixed_maintenance" value="{{ $old('fixed_maintenance', 0) }}">
                @error('fixed_maintenance')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-4">
                <label for="water_unit_rate">Water Unit Rate (₹ per unit)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('water_unit_rate') is-invalid @enderror" id="water_unit_rate" name="water_unit_rate" value="{{ $old('water_unit_rate', 0) }}">
                @error('water_unit_rate')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="start_date">Access Start Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ $oldDate('start_date', now()->format('Y-m-d')) }}" required>
                @error('start_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="end_date">Access End Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ $oldDate('end_date', now()->addYear()->format('Y-m-d')) }}" required>
                @error('end_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="status">Status <span class="text-danger">*</span></label>
                <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (['active', 'inactive', 'expired', 'archived'] as $status)
                        <option value="{{ $status }}" {{ $old('status', 'active') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3 d-flex align-items-end">
                <div class="form-check mr-3">
                    <input type="hidden" name="is_trial" value="0">
                    <input type="checkbox" class="form-check-input" id="is_trial" name="is_trial" value="1" {{ $old('is_trial') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_trial">Trial</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="payment_verified" value="0">
                    <input type="checkbox" class="form-check-input" id="payment_verified" name="payment_verified" value="1" {{ $old('payment_verified') ? 'checked' : '' }}>
                    <label class="form-check-label" for="payment_verified">Payment Verified</label>
                </div>
            </div>
        </div>

        <hr>
        <h5>Primary Contact (optional)</h5>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="admin_name">Contact Name</label>
                <input type="text" class="form-control @error('admin_name') is-invalid @enderror" id="admin_name" name="admin_name" value="{{ $old('admin_name') }}">
                @error('admin_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-4">
                <label for="admin_email">Contact Email</label>
                <input type="email" class="form-control @error('admin_email') is-invalid @enderror" id="admin_email" name="admin_email" value="{{ $old('admin_email') }}">
                @error('admin_email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-4">
                <label for="admin_phone">Contact Phone</label>
                <input type="text" class="form-control @error('admin_phone') is-invalid @enderror" id="admin_phone" name="admin_phone" value="{{ $old('admin_phone') }}">
                @error('admin_phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        @isset($modules)
            <hr>
            <h5>Modules</h5>
            <p class="text-muted">Core modules are enabled by default. Choose which additional modules this society gets access to.</p>
            <div class="form-row">
                @foreach ($modules as $module)
                    <div class="form-check col-md-3 mb-2">
                        <input type="checkbox" class="form-check-input" id="module_{{ $module->id }}" name="modules[]" value="{{ $module->id }}"
                            {{ (in_array($module->id, old('modules', [])) || $module->is_core) ? 'checked' : '' }}
                            {{ $module->is_core ? 'disabled' : '' }}>
                        <label class="form-check-label" for="module_{{ $module->id }}">
                            {{ $module->display_name }}
                            @if ($module->is_core)
                                <span class="badge badge-secondary">Core</span>
                            @endif
                        </label>
                        @if ($module->is_core)
                            {{-- disabled checkboxes don't submit; force core modules through regardless of user input --}}
                            <input type="hidden" name="modules[]" value="{{ $module->id }}">
                        @endif
                    </div>
                @endforeach
            </div>
        @endisset
    </div>
</div>
