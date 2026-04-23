<div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Home name</label>
            <input class="form-control focus-ring-brand" id="name" name="name" value="{{ old('name', $home->name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="registration_number">Registration number</label>
            <input class="form-control focus-ring-brand" id="registration_number" name="registration_number" value="{{ old('registration_number', $home->registration_number) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="care_type">Care type</label>
            <input class="form-control focus-ring-brand" id="care_type" name="care_type" value="{{ old('care_type', $home->care_type) }}" placeholder="Residential, nursing, supported living">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="capacity">Capacity</label>
            <input class="form-control focus-ring-brand" id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity', $home->capacity) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="status">Status</label>
            <select class="form-select focus-ring-brand" id="status" name="status" required>
                @foreach (['active' => 'Active', 'onboarding' => 'Onboarding', 'inactive' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $home->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <hr class="my-4">

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="phone">Phone</label>
            <input class="form-control focus-ring-brand" id="phone" name="phone" value="{{ old('phone', $home->phone) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="email">Email</label>
            <input class="form-control focus-ring-brand" id="email" name="email" type="email" value="{{ old('email', $home->email) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="website">Website</label>
            <input class="form-control focus-ring-brand" id="website" name="website" type="url" value="{{ old('website', $home->website) }}">
        </div>
    </div>

    <hr class="my-4">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="address_line_1">Address line 1</label>
            <input class="form-control focus-ring-brand" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $home->address_line_1) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="address_line_2">Address line 2</label>
            <input class="form-control focus-ring-brand" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $home->address_line_2) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="city">City</label>
            <input class="form-control focus-ring-brand" id="city" name="city" value="{{ old('city', $home->city) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="county">County</label>
            <input class="form-control focus-ring-brand" id="county" name="county" value="{{ old('county', $home->county) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="postcode">Postcode</label>
            <input class="form-control focus-ring-brand" id="postcode" name="postcode" value="{{ old('postcode', $home->postcode) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="country">Country</label>
            <input class="form-control focus-ring-brand" id="country" name="country" value="{{ old('country', $home->country ?: 'United Kingdom') }}" required>
        </div>
    </div>

    <hr class="my-4">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="manager_id">Home manager</label>
            <select class="form-select focus-ring-brand" id="manager_id" name="manager_id">
                <option value="">Assign later</option>
                @foreach ($managers as $manager)
                    <option value="{{ $manager->id }}" @selected((int) old('manager_id', $home->manager_id) === (int) $manager->id)>{{ $manager->name }} ({{ $manager->email }})</option>
                @endforeach
            </select>
            <p class="form-text">Assigning a manager links that user to this home and adds the Home Manager role when available.</p>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="logo">Home logo</label>
            <input class="form-control focus-ring-brand" id="logo" name="logo" type="file" accept="image/*">
            <p class="form-text">PNG, JPG, or SVG-friendly raster upload up to 2 MB.</p>
            @if ($home->logoUrl())
                <div class="d-flex align-items-center gap-3 mt-2">
                    <img src="{{ $home->logoUrl() }}" alt="{{ $home->name }} logo" class="rounded border" width="64" height="64">
                    <label class="form-check mb-0">
                        <input class="form-check-input" name="remove_logo" type="checkbox" value="1">
                        <span class="form-check-label">Remove current logo</span>
                    </label>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
