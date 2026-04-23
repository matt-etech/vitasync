<div>
    <div class="mb-3">
        <label class="form-label" for="name">Permission name</label>
        <input class="form-control focus-ring-brand" id="name" name="name" value="{{ old('name', $permission->name) }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control focus-ring-brand" id="description" name="description" rows="3">{{ old('description', $permission->description) }}</textarea>
    </div>

    <label class="form-check mb-3">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $permission->is_active ?? true))>
        <span class="form-check-label">Active permission</span>
    </label>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
