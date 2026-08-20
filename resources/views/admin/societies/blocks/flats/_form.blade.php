<div class="row">
    <div class="col-md-6 form-group">
        <label for="flat_number">Flat Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('flat_number') is-invalid @enderror"
            id="flat_number" name="flat_number" value="{{ old('flat_number', $flat->flat_number ?? $block->block_number . '-') }}"
            placeholder="e.g. {{ $block->block_number }}-201" required>
        @error('flat_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="floor_number">Floor <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('floor_number') is-invalid @enderror"
            id="floor_number" name="floor_number" value="{{ old('floor_number', $flat->floor_number ?? '') }}" required>
        @error('floor_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label for="flat_type">Flat Type <span class="text-danger">*</span></label>
        <select class="form-control @error('flat_type') is-invalid @enderror" id="flat_type" name="flat_type" required>
            @foreach (['1BHK', '2BHK', '3BHK', '4BHK', 'Duplex', 'Penthouse', 'Other'] as $type)
                <option value="{{ $type }}" {{ old('flat_type', $flat->flat_type ?? '2BHK') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
        @error('flat_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="area_sqft">Area (sq ft)</label>
        <input type="number" step="0.01" min="0" class="form-control @error('area_sqft') is-invalid @enderror"
            id="area_sqft" name="area_sqft" value="{{ old('area_sqft', $flat->area_sqft ?? '') }}">
        @error('area_sqft')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label for="ownership_type">Ownership <span class="text-danger">*</span></label>
        <select class="form-control @error('ownership_type') is-invalid @enderror" id="ownership_type" name="ownership_type" required>
            @foreach (['owned' => 'Owned', 'rented' => 'Rented', 'vacant' => 'Vacant'] as $value => $label)
                <option value="{{ $value }}" {{ old('ownership_type', $flat->ownership_type ?? 'vacant') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('ownership_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="owner_name">Owner Name</label>
        <input type="text" class="form-control @error('owner_name') is-invalid @enderror"
            id="owner_name" name="owner_name" value="{{ old('owner_name', $flat->owner_name ?? '') }}">
        @error('owner_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label for="car_parking_slot">Car Parking Slot</label>
        <input type="text" class="form-control @error('car_parking_slot') is-invalid @enderror"
            id="car_parking_slot" name="car_parking_slot" value="{{ old('car_parking_slot', $flat->car_parking_slot ?? '') }}">
        @error('car_parking_slot')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="bike_parking_slot">Bike Parking Slot</label>
        <input type="text" class="form-control @error('bike_parking_slot') is-invalid @enderror"
            id="bike_parking_slot" name="bike_parking_slot" value="{{ old('bike_parking_slot', $flat->bike_parking_slot ?? '') }}">
        @error('bike_parking_slot')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group">
    <label for="status">Status <span class="text-danger">*</span></label>
    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
        @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'under_construction' => 'Under Construction', 'under_maintenance' => 'Under Maintenance'] as $value => $label)
            <option value="{{ $value }}" {{ old('status', $flat->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('status')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
