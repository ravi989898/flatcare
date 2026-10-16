<div class="form-group">
    <label for="flat_number">Flat Number <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('flat_number') is-invalid @enderror"
        id="flat_number" name="flat_number" value="{{ old('flat_number', $flat->flat_number ?? '') }}"
        placeholder="e.g. {{ $block->block_number }}-101" maxlength="255" autofocus required>
    @error('flat_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="mobile_number">Resident Mobile Number</label>
    <input type="tel" class="form-control @error('mobile_number') is-invalid @enderror"
        id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $flat->mobile_number ?? '') }}"
        placeholder="e.g. 9876543210" maxlength="10" data-validate="phone">
    <small class="form-text text-muted">
        This is the OTP-login number for the FlatCare resident app — whoever verifies this number in
        the app is signed in as this flat's resident. Leave blank if no one should be able to log in yet.
    </small>
    @error('mobile_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@php
    $residentUser ??= null;
    // Auto-provisioned accounts (first OTP login, or no name/email given here
    // before one) carry a placeholder name/email - not worth showing back to
    // the admin as if it were real resident-entered data.
    $residentName = str_starts_with((string) $residentUser?->name, 'Resident of ') ? null : $residentUser?->name;
    $residentEmail = str_ends_with((string) $residentUser?->email, '@resident.flatcare.local') ? null : $residentUser?->email;
@endphp
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="resident_name">Resident Name</label>
        <input type="text" class="form-control @error('resident_name') is-invalid @enderror"
            id="resident_name" name="resident_name" value="{{ old('resident_name', $residentName) }}" maxlength="255">
        @error('resident_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="resident_email">Resident Email</label>
        <input type="email" class="form-control @error('resident_email') is-invalid @enderror"
            id="resident_email" name="resident_email" value="{{ old('resident_email', $residentEmail) }}" maxlength="255">
        @error('resident_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<small class="form-text text-muted d-block mb-3">
    Both optional. This is the same resident account the FlatCare app's Profile screen edits — updates here or from the app keep each other in sync.
</small>
