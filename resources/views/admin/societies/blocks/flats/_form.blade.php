<div class="form-group">
    <label for="flat_number">Flat Number <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('flat_number') is-invalid @enderror"
        id="flat_number" name="flat_number" value="{{ old('flat_number', $flat->flat_number ?? '') }}"
        placeholder="e.g. {{ $block->block_number }}-101" autofocus required>
    @error('flat_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="mobile_number">Resident Mobile Number</label>
    <input type="text" class="form-control @error('mobile_number') is-invalid @enderror"
        id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $flat->mobile_number ?? '') }}"
        placeholder="e.g. 9876543210">
    <small class="form-text text-muted">
        This is the OTP-login number for the FlatCare resident app — whoever verifies this number in
        the app is signed in as this flat's resident. Leave blank if no one should be able to log in yet.
    </small>
    @error('mobile_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
