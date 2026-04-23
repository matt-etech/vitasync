<div>
    <div class="mb-3">
        <label class="form-label" for="name">Role name</label>
        <input class="form-control focus-ring-brand" id="name" name="name" value="{{ old('name', $role->name) }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control focus-ring-brand" id="description" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
    </div>

    <label class="form-check mb-3">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $role->is_active ?? true))>
        <span class="form-check-label">Active role</span>
    </label>

    <fieldset class="mt-4">
        <legend class="h6">Permissions</legend>
        <div class="row g-3">
            @forelse ($permissions as $permission)
                <label class="col-md-6">
                    <span class="d-flex gap-3 border rounded p-3 h-100">
                    <input class="form-check-input mt-1" name="permissions[]" type="checkbox" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $selectedPermissions), true))>
                    <span>
                        <span class="d-block fw-medium">{{ $permission->name }}</span>
                        <span class="d-block small text-secondary">{{ $permission->description ?: 'No description' }}</span>
                    </span>
                    </span>
                </label>
            @empty
                <div class="col-12">
                    <p class="alert alert-warning mb-0">Create permissions before assigning them to roles.</p>
                </div>
            @endforelse
        </div>
    </fieldset>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
