<div class="form-group">
    <label for="block_number">Block Number <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('block_number') is-invalid @enderror"
        id="block_number" name="block_number" value="{{ old('block_number', $block->block_number ?? '') }}"
        placeholder="e.g. Block-A" autofocus required>
    @error('block_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
