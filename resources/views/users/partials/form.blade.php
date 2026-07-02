@php
    $useOldInput = $useOldInput ?? true;
    $fieldValue = fn (string $field, mixed $default = null) => $useOldInput ? old($field, $default) : $default;
    $fieldArray = fn (string $field, array $default = []) => $useOldInput ? old($field, $default) : $default;
@endphp

<div>
    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Account Details</h2>
            <p class="form-section-description">Create or update the user profile and home assignment.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control focus-ring-brand {{ $useOldInput && $errors->has('name') ? 'is-invalid' : '' }}" id="name" name="name" value="{{ $fieldValue('name', $user->name) }}" required>
                @if ($useOldInput && $errors->has('name'))
                    <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control focus-ring-brand {{ $useOldInput && $errors->has('email') ? 'is-invalid' : '' }}" id="email" name="email" type="email" value="{{ $fieldValue('email', $user->email) }}" required>
                @if ($useOldInput && $errors->has('email'))
                    <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="home_id">Home</label>
                <select class="form-select focus-ring-brand {{ $useOldInput && $errors->has('home_id') ? 'is-invalid' : '' }}" id="home_id" name="home_id">
                    <option value="">Platform-wide user</option>
                    @foreach ($homes as $home)
                        <option value="{{ $home->id }}" @selected((int) $fieldValue('home_id', $user->home_id) === (int) $home->id)>{{ $home->name }}</option>
                    @endforeach
                </select>
                @if ($useOldInput && $errors->has('home_id'))
                    <div class="invalid-feedback">{{ $errors->first('home_id') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="job_title">Job title</label>
                <input class="form-control focus-ring-brand {{ $useOldInput && $errors->has('job_title') ? 'is-invalid' : '' }}" id="job_title" name="job_title" value="{{ $fieldValue('job_title', $user->job_title) }}">
                @if ($useOldInput && $errors->has('job_title'))
                    <div class="invalid-feedback">{{ $errors->first('job_title') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control focus-ring-brand {{ $useOldInput && $errors->has('phone') ? 'is-invalid' : '' }}" id="phone" name="phone" value="{{ $fieldValue('phone', $user->phone) }}">
                @if ($useOldInput && $errors->has('phone'))
                    <div class="invalid-feedback">{{ $errors->first('phone') }}</div>
                @endif
            </div>
            <div class="col-12">
                <label class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input {{ $useOldInput && $errors->has('is_active') ? 'is-invalid' : '' }}" name="is_active" type="checkbox" value="1" @checked((bool) $fieldValue('is_active', $user->is_active ?? true))>
                    <span class="form-check-label">Active account</span>
                    @if ($useOldInput && $errors->has('is_active'))
                        <div class="invalid-feedback">{{ $errors->first('is_active') }}</div>
                    @endif
                </label>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Security</h2>
            <p class="form-section-description">Set a password for new users or leave it unchanged when editing.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="password">Password</label>
                <input class="form-control focus-ring-brand {{ $useOldInput && $errors->has('password') ? 'is-invalid' : '' }}" id="password" name="password" type="password" @required($passwordRequired)>
                @if ($useOldInput && $errors->has('password'))
                    <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                @endif
                @unless ($passwordRequired)
                    <p class="form-text">Leave blank to keep the current password.</p>
                @endunless
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input class="form-control focus-ring-brand {{ $useOldInput && $errors->has('password_confirmation') ? 'is-invalid' : '' }}" id="password_confirmation" name="password_confirmation" type="password" @required($passwordRequired)>
                @if ($useOldInput && $errors->has('password_confirmation'))
                    <div class="invalid-feedback">{{ $errors->first('password_confirmation') }}</div>
                @endif
            </div>
        </div>
    </section>

    <section class="form-section">
        <fieldset>
            <div class="form-section-header">
                <legend class="form-section-title">Roles</legend>
                <p class="form-section-description">Assign role-based access before adding direct permissions.</p>
            </div>
            <div class="row g-3">
                @forelse ($roles as $role)
                    <label class="col-md-6">
                        <span class="choice-card">
                            <input class="form-check-input {{ $useOldInput && ($errors->has('roles') || $errors->has('roles.*')) ? 'is-invalid' : '' }}" name="roles[]" type="checkbox" value="{{ $role->id }}" @checked(in_array($role->id, $fieldArray('roles', $selectedRoles), true))>
                            <span>
                                <span class="d-block fw-medium">{{ $role->name }}</span>
                                <span class="d-block small text-secondary">{{ $role->description ?: 'No description' }}</span>
                            </span>
                        </span>
                    </label>
                @empty
                    <div class="col-12">
                        <p class="alert alert-warning mb-0">Create roles before assigning them to users.</p>
                    </div>
                @endforelse
            </div>
            @if ($useOldInput && $errors->has('roles'))
                <p class="text-danger small fw-semibold mt-2 mb-0">{{ $errors->first('roles') }}</p>
            @endif
            @if ($useOldInput && $errors->has('roles.*'))
                <p class="text-danger small fw-semibold mt-2 mb-0">{{ $errors->first('roles.*') }}</p>
            @endif
        </fieldset>
    </section>

    <section class="form-section">
        <fieldset>
            <div class="form-section-header">
                <legend class="form-section-title">Direct Permissions</legend>
                <p class="form-section-description">Use direct permissions only when role access is not specific enough.</p>
            </div>
            <div class="row g-3">
                @forelse ($permissions as $permission)
                    <label class="col-md-6">
                        <span class="choice-card">
                            <input class="form-check-input {{ $useOldInput && ($errors->has('permissions') || $errors->has('permissions.*')) ? 'is-invalid' : '' }}" name="permissions[]" type="checkbox" value="{{ $permission->id }}" @checked(in_array($permission->id, $fieldArray('permissions', $selectedPermissions), true))>
                            <span>
                                <span class="d-block fw-medium">{{ $permission->name }}</span>
                                <span class="d-block small text-secondary">{{ $permission->description ?: 'No description' }}</span>
                            </span>
                        </span>
                    </label>
                @empty
                    <div class="col-12">
                        <p class="alert alert-warning mb-0">Create permissions before assigning direct access.</p>
                    </div>
                @endforelse
            </div>
            @if ($useOldInput && $errors->has('permissions'))
                <p class="text-danger small fw-semibold mt-2 mb-0">{{ $errors->first('permissions') }}</p>
            @endif
            @if ($useOldInput && $errors->has('permissions.*'))
                <p class="text-danger small fw-semibold mt-2 mb-0">{{ $errors->first('permissions.*') }}</p>
            @endif
        </fieldset>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
