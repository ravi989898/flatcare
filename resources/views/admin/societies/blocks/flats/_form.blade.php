<div class="form-group">
    <label for="flat_number">Flat Number <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('flat_number') is-invalid @enderror"
        id="flat_number" name="flat_number" value="{{ old('flat_number', $flat->flat_number ?? '') }}"
        placeholder="e.g. {{ $block->block_number }}-101" autofocus required>
    @error('flat_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
