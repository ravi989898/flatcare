@php
    /** @var \App\Models\RoleDefinition|null $role */
    $role = $role ?? null;
@endphp

<div class="form-group">
    <label for="name">Role Key <span class="text-danger">*</span></label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $role?->name) }}"
        placeholder="e.g. accountant"
        pattern="[a-z][a-z0-9_]*"
        {{ $role?->is_system_role ? 'readonly' : '' }} required>
    @error('name')
        <span class="invalid-feedback d-block">{{ $message }}</span>
    @enderror
    <small class="form-text text-muted">
        Lowercase letters, numbers and underscores only. This is what ties this row to each society's own
        <code>roles.name</code> — it can't be changed once a role is a system role.
    </small>
</div>

<div class="form-group">
    <label for="display_name">Display Name <span class="text-danger">*</span></label>
    <input type="text" name="display_name" id="display_name" class="form-control @error('display_name') is-invalid @enderror"
        value="{{ old('display_name', $role?->display_name) }}" required>
    @error('display_name')
        <span class="invalid-feedback d-block">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" id="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $role?->description) }}</textarea>
    @error('description')
        <span class="invalid-feedback d-block">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label for="priority">Priority</label>
    <input type="number" name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror"
        value="{{ old('priority', $role?->priority ?? 0) }}" min="0" max="1000">
    @error('priority')
        <span class="invalid-feedback d-block">{{ $message }}</span>
    @enderror
    <small class="form-text text-muted">Higher priority roles are treated as more senior (e.g. when a user has more than one role).</small>
</div>
