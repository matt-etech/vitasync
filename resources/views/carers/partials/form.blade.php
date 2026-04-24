<div>
    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Carer Account</h2>
            <p class="form-section-description">Create or update a dedicated carer login that can later be assigned to visits.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control focus-ring-brand" id="name" name="name" value="{{ old('name', $carer->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control focus-ring-brand" id="email" name="email" type="email" value="{{ old('email', $carer->email) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="home_id">Home</label>
                <select class="form-select focus-ring-brand" id="home_id" name="home_id">
                    <option value="">Platform-wide carer</option>
                    @foreach ($homes as $home)
                        <option value="{{ $home->id }}" @selected((int) old('home_id', $carer->home_id) === (int) $home->id)>{{ $home->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="job_title">Job title</label>
                <input class="form-control focus-ring-brand" id="job_title" name="job_title" value="{{ old('job_title', $carer->job_title ?: 'Carer') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control focus-ring-brand" id="phone" name="phone" value="{{ old('phone', $carer->phone) }}">
            </div>
            <div class="col-12">
                <label class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $carer->is_active ?? true))>
                    <span class="form-check-label">Active account</span>
                </label>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Login Password</h2>
            <p class="form-section-description">Use the default password `password` for the starter account, or set a different password for this carer.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="password">Password</label>
                <input class="form-control focus-ring-brand" id="password" name="password" type="password" @required($passwordRequired)>
                @unless ($passwordRequired)
                    <p class="form-text">Leave blank to keep the current password.</p>
                @endunless
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input class="form-control focus-ring-brand" id="password_confirmation" name="password_confirmation" type="password" @required($passwordRequired)>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
