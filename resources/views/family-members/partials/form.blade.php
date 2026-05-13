<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="client_id_{{ $member->id ?? 'new' }}">Client</label>
        <select class="form-select focus-ring-brand" id="client_id_{{ $member->id ?? 'new' }}" name="client_id" required>
            <option value="">Select client</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" @selected((int) old('client_id', $member->client_id) === (int) $client->id)>{{ $client->fullName() }} - {{ $client->home->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="name_{{ $member->id ?? 'new' }}">Name</label>
        <input class="form-control focus-ring-brand" id="name_{{ $member->id ?? 'new' }}" name="name" value="{{ old('name', $member->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="relationship_{{ $member->id ?? 'new' }}">Relationship</label>
        <input class="form-control focus-ring-brand" id="relationship_{{ $member->id ?? 'new' }}" name="relationship" value="{{ old('relationship', $member->relationship) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email_{{ $member->id ?? 'new' }}">Email</label>
        <input class="form-control focus-ring-brand" id="email_{{ $member->id ?? 'new' }}" name="email" type="email" value="{{ old('email', $member->email) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone_{{ $member->id ?? 'new' }}">Phone</label>
        <input class="form-control focus-ring-brand" id="phone_{{ $member->id ?? 'new' }}" name="phone" value="{{ old('phone', $member->phone) }}">
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <input type="hidden" name="is_active" value="0">
        <label class="choice-card w-100" for="is_active_{{ $member->id ?? 'new' }}">
            <input class="form-check-input" id="is_active_{{ $member->id ?? 'new' }}" name="is_active" type="checkbox" value="1" @checked(old('is_active', $member->is_active ?? true))>
            <span><span class="d-block fw-bold">Family access active</span><span class="d-block text-secondary small">Disabled accounts cannot log in.</span></span>
        </label>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_{{ $member->id ?? 'new' }}">Family portal login password</label>
        <input class="form-control focus-ring-brand" id="password_{{ $member->id ?? 'new' }}" name="password" type="password" autocomplete="new-password" @if($requirePassword) required @endif>
        <div class="form-text">{{ $requirePassword ? 'This creates the password they will use to log in.' : 'Leave blank to keep the current login password.' }}</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_confirmation_{{ $member->id ?? 'new' }}">Confirm password</label>
        <input class="form-control focus-ring-brand" id="password_confirmation_{{ $member->id ?? 'new' }}" name="password_confirmation" type="password" autocomplete="new-password" @if($requirePassword) required @endif>
    </div>

    <div class="col-12">
        <hr>
        <p class="section-kicker mb-2">Consent controls</p>
        <h3 class="h5 fw-bold">Family access permissions</h3>
        <p class="text-secondary mb-0">Grant the minimum access needed. Internal staff notes, full audit logs, other clients, staff records, and restricted investigations are never included in family portal responses.</p>
    </div>

    @foreach ($accessLabels as $field => $meta)
        <div class="col-md-4">
            <input type="hidden" name="{{ $field }}" value="0">
            <label class="choice-card h-100" for="{{ $field }}_{{ $member->id ?? 'new' }}">
                <input class="form-check-input" id="{{ $field }}_{{ $member->id ?? 'new' }}" name="{{ $field }}" type="checkbox" value="1" @checked(old($field, (bool) $member->{$field}))>
                <span><span class="d-block fw-bold">{{ $meta['label'] }}</span><span class="d-block text-secondary small">{{ $meta['help'] }}</span></span>
            </label>
        </div>
    @endforeach

    <div class="col-12">
        <label class="form-label" for="access_notes_{{ $member->id ?? 'new' }}">Access notes</label>
        <textarea class="form-control focus-ring-brand" id="access_notes_{{ $member->id ?? 'new' }}" name="access_notes" rows="3">{{ old('access_notes', $member->access_notes) }}</textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save family access</button>
    </div>
</div>
