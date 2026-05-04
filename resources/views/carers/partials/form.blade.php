@php
    $formId = 'carerAccessForm'.($carer->id ?: 'New');
@endphp

<div>
    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Login & Access</h2>
            <p class="form-section-description">Create the carer's login first. Onboarding assessment details are completed after this account exists.</p>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <strong>Role:</strong> Carer. This account will appear in Users and Carers once created.
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_name">Display name</label>
                <input class="form-control focus-ring-brand @error('name') is-invalid @enderror" id="{{ $formId }}_name" name="name" value="{{ old('name', $carer->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_email">Email address</label>
                <input class="form-control focus-ring-brand @error('email') is-invalid @enderror" id="{{ $formId }}_email" name="email" type="email" value="{{ old('email', $carer->email) }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="{{ $formId }}_home_id">Home</label>
                <select class="form-select focus-ring-brand @error('home_id') is-invalid @enderror" id="{{ $formId }}_home_id" name="home_id" required>
                    <option value="">Select home</option>
                    @foreach ($homes as $home)
                        <option value="{{ $home->id }}" @selected((int) old('home_id', $carer->home_id) === (int) $home->id)>{{ $home->name }}</option>
                    @endforeach
                </select>
                @error('home_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="{{ $formId }}_job_title">Job title</label>
                <input class="form-control focus-ring-brand @error('job_title') is-invalid @enderror" id="{{ $formId }}_job_title" name="job_title" value="{{ old('job_title', $carer->job_title ?: 'Carer') }}">
                @error('job_title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="{{ $formId }}_phone">Phone</label>
                <input class="form-control focus-ring-brand @error('phone') is-invalid @enderror" id="{{ $formId }}_phone" name="phone" value="{{ old('phone', $carer->phone) }}">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input @error('is_active') is-invalid @enderror" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $carer->is_active ?? true))>
                    <span class="form-check-label">Enable login</span>
                </label>
                @error('is_active')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Password</h2>
            <p class="form-section-description">Set the password the carer will use to sign in.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_password">Password</label>
                <input class="form-control focus-ring-brand @error('password') is-invalid @enderror" id="{{ $formId }}_password" name="password" type="password" @required($passwordRequired)>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @unless ($passwordRequired)
                    <p class="form-text">Leave blank to keep the current password.</p>
                @endunless
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $formId }}_password_confirmation">Confirm password</label>
                <input class="form-control focus-ring-brand @error('password_confirmation') is-invalid @enderror" id="{{ $formId }}_password_confirmation" name="password_confirmation" type="password" @required($passwordRequired)>
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        @isset($cancelUrl)
            <a class="btn btn-outline-secondary fw-semibold" href="{{ $cancelUrl }}">Cancel</a>
        @else
            <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
        @endisset
    </div>
</div>
