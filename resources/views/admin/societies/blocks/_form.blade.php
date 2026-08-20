<div class="form-group">
    <label for="name">Block Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror"
        id="name" name="name" value="{{ old('name', $block->name ?? '') }}" placeholder="e.g. Block A" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="block_number">Block Number <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('block_number') is-invalid @enderror"
        id="block_number" name="block_number" value="{{ old('block_number', $block->block_number ?? '') }}" placeholder="e.g. A" required>
    <small class="form-text text-muted">Short code used as the prefix for this block's flat numbers (e.g. A-201).</small>
    @error('block_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="description">Description</label>
    <input type="text" class="form-control @error('description') is-invalid @enderror"
        id="description" name="description" value="{{ old('description', $block->description ?? '') }}">
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label for="total_floors">Total Floors</label>
        <input type="number" min="0" class="form-control @error('total_floors') is-invalid @enderror"
            id="total_floors" name="total_floors" value="{{ old('total_floors', $block->total_floors ?? '') }}">
        @error('total_floors')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="block_admin_contact">Block Admin Contact</label>
        <input type="text" class="form-control @error('block_admin_contact') is-invalid @enderror"
            id="block_admin_contact" name="block_admin_contact" value="{{ old('block_admin_contact', $block->block_admin_contact ?? '') }}">
        @error('block_admin_contact')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group">
    <label for="status">Status <span class="text-danger">*</span></label>
    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
        @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'under_construction' => 'Under Construction'] as $value => $label)
            <option value="{{ $value }}" {{ old('status', $block->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('status')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
