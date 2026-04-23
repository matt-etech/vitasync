<div>
    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Client Details</h2>
            <p class="form-section-description">Capture the core identity details needed to start onboarding safely.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="first_name">First name</label>
                <input class="form-control focus-ring-brand" id="first_name" name="first_name" value="{{ old('first_name', $client->first_name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="last_name">Last name</label>
                <input class="form-control focus-ring-brand" id="last_name" name="last_name" value="{{ old('last_name', $client->last_name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="date_of_birth">Date of birth</label>
                <input class="form-control focus-ring-brand" id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $client->date_of_birth?->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="gender">Gender</label>
                <select class="form-select focus-ring-brand" id="gender" name="gender">
                    <option value="">Select gender</option>
                    @foreach (['Male', 'Female'] as $gender)
                        <option value="{{ $gender }}" @selected(old('gender', $client->gender) === $gender)>{{ $gender }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select focus-ring-brand" id="status" name="status" required>
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $client->status ?: 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Contact And Address</h2>
            <p class="form-section-description">Record how the client or their representative can be contacted.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control focus-ring-brand" id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control focus-ring-brand" id="email" name="email" type="email" value="{{ old('email', $client->email) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="address">Address</label>
                <textarea class="form-control focus-ring-brand" id="address" name="address" rows="3">{{ old('address', $client->address) }}</textarea>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Emergency Contact</h2>
            <p class="form-section-description">Keep escalation contact details visible before care delivery starts.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="emergency_contact_name">Emergency contact name</label>
                <input class="form-control focus-ring-brand" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $client->emergency_contact_name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="emergency_contact_phone">Emergency contact phone</label>
                <input class="form-control focus-ring-brand" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $client->emergency_contact_phone) }}">
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Home Assignment</h2>
            <p class="form-section-description">Assign the client to the home responsible for onboarding and care coordination.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="home_id">Home</label>
                <select class="form-select focus-ring-brand" id="home_id" name="home_id" required>
                    <option value="">Select home</option>
                    @foreach ($homes as $home)
                        <option value="{{ $home->id }}" @selected((int) old('home_id', $client->home_id) === (int) $home->id)>{{ $home->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
