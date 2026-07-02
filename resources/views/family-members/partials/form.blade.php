<div class="row g-3">
    @php
        $useOldInput = $useOldInput ?? true;
        $fieldValue = fn (string $field, mixed $fallback = null) => $useOldInput ? old($field, $fallback) : $fallback;
        $fieldArray = fn (string $field, array $fallback = []) => $useOldInput ? old($field, $fallback) : $fallback;
        $fieldHasError = fn (string $field): bool => $useOldInput && ($errors->has($field) || $errors->has($field.'.*'));
        $fieldError = fn (string $field): ?string => $fieldHasError($field) ? ($errors->first($field) ?: $errors->first($field.'.*')) : null;
        $assignedClientIds = collect($fieldArray('client_ids', $member->exists ? $member->clients->pluck('id')->all() : []))
            ->map(fn ($id) => (int) $id)
            ->push((int) $fieldValue('client_id', $member->client_id))
            ->filter()
            ->unique()
            ->all();
    @endphp

    <div class="col-md-6">
        <label class="form-label" for="client_id_{{ $member->id ?? 'new' }}">Client</label>
        <select class="form-select focus-ring-brand {{ $fieldHasError('client_id') ? 'is-invalid' : '' }}" id="client_id_{{ $member->id ?? 'new' }}" name="client_id" required>
            <option value="">Select client</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" @selected((int) $fieldValue('client_id', $member->client_id) === (int) $client->id)>{{ $client->fullName() }} - {{ $client->home->name }}</option>
            @endforeach
        </select>
        @if ($fieldError('client_id'))
            <div class="invalid-feedback">{{ $fieldError('client_id') }}</div>
        @endif
        <div class="form-text">This is the default client shown after login.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="client_ids_{{ $member->id ?? 'new' }}">Additional assigned clients</label>
        <select class="form-select focus-ring-brand {{ $fieldHasError('client_ids') ? 'is-invalid' : '' }}" id="client_ids_{{ $member->id ?? 'new' }}" name="client_ids[]" multiple size="4">
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" @selected(in_array((int) $client->id, $assignedClientIds, true))>{{ $client->fullName() }} - {{ $client->home->name }}</option>
            @endforeach
        </select>
        @if ($fieldError('client_ids'))
            <div class="invalid-feedback">{{ $fieldError('client_ids') }}</div>
        @endif
        <div class="form-text">Hold Ctrl to select more than one. The default client is included automatically.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="name_{{ $member->id ?? 'new' }}">Name</label>
        <input class="form-control focus-ring-brand {{ $fieldHasError('name') ? 'is-invalid' : '' }}" id="name_{{ $member->id ?? 'new' }}" name="name" value="{{ $fieldValue('name', $member->name) }}" required>
        @if ($fieldError('name'))
            <div class="invalid-feedback">{{ $fieldError('name') }}</div>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="relationship_{{ $member->id ?? 'new' }}">Relationship</label>
        <input class="form-control focus-ring-brand {{ $fieldHasError('relationship') ? 'is-invalid' : '' }}" id="relationship_{{ $member->id ?? 'new' }}" name="relationship" value="{{ $fieldValue('relationship', $member->relationship) }}">
        @if ($fieldError('relationship'))
            <div class="invalid-feedback">{{ $fieldError('relationship') }}</div>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email_{{ $member->id ?? 'new' }}">Email</label>
        <input class="form-control focus-ring-brand {{ $fieldHasError('email') ? 'is-invalid' : '' }}" id="email_{{ $member->id ?? 'new' }}" name="email" type="email" value="{{ $fieldValue('email', $member->email) }}" required>
        @if ($fieldError('email'))
            <div class="invalid-feedback">{{ $fieldError('email') }}</div>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone_{{ $member->id ?? 'new' }}">Phone</label>
        <input class="form-control focus-ring-brand {{ $fieldHasError('phone') ? 'is-invalid' : '' }}" id="phone_{{ $member->id ?? 'new' }}" name="phone" value="{{ $fieldValue('phone', $member->phone) }}">
        @if ($fieldError('phone'))
            <div class="invalid-feedback">{{ $fieldError('phone') }}</div>
        @endif
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <input type="hidden" name="is_active" value="0">
        <label class="choice-card w-100" for="is_active_{{ $member->id ?? 'new' }}">
            <input class="form-check-input {{ $fieldHasError('is_active') ? 'is-invalid' : '' }}" id="is_active_{{ $member->id ?? 'new' }}" name="is_active" type="checkbox" value="1" @checked($fieldValue('is_active', $member->is_active ?? true))>
            <span><span class="d-block fw-bold">Family access active</span><span class="d-block text-secondary small">Disabled accounts cannot log in.</span></span>
        </label>
        @if ($fieldError('is_active'))
            <div class="invalid-feedback d-block">{{ $fieldError('is_active') }}</div>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_{{ $member->id ?? 'new' }}">Family portal login password</label>
        <input class="form-control focus-ring-brand {{ $fieldHasError('password') ? 'is-invalid' : '' }}" id="password_{{ $member->id ?? 'new' }}" name="password" type="password" autocomplete="new-password" @if($requirePassword) required @endif>
        @if ($fieldError('password'))
            <div class="invalid-feedback">{{ $fieldError('password') }}</div>
        @endif
        <div class="form-text">{{ $requirePassword ? 'This creates the password they will use to log in.' : 'Leave blank to keep the current login password.' }}</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_confirmation_{{ $member->id ?? 'new' }}">Confirm password</label>
        <input class="form-control focus-ring-brand {{ $fieldHasError('password_confirmation') ? 'is-invalid' : '' }}" id="password_confirmation_{{ $member->id ?? 'new' }}" name="password_confirmation" type="password" autocomplete="new-password" @if($requirePassword) required @endif>
        @if ($fieldError('password_confirmation'))
            <div class="invalid-feedback">{{ $fieldError('password_confirmation') }}</div>
        @endif
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
                <input class="form-check-input {{ $fieldHasError($field) ? 'is-invalid' : '' }}" id="{{ $field }}_{{ $member->id ?? 'new' }}" name="{{ $field }}" type="checkbox" value="1" @checked($fieldValue($field, (bool) $member->{$field}))>
                <span><span class="d-block fw-bold">{{ $meta['label'] }}</span><span class="d-block text-secondary small">{{ $meta['help'] }}</span></span>
            </label>
            @if ($fieldError($field))
                <div class="invalid-feedback d-block">{{ $fieldError($field) }}</div>
            @endif
        </div>
    @endforeach

    <div class="col-12">
        <label class="form-label" for="access_notes_{{ $member->id ?? 'new' }}">Access notes</label>
        <textarea class="form-control focus-ring-brand {{ $fieldHasError('access_notes') ? 'is-invalid' : '' }}" id="access_notes_{{ $member->id ?? 'new' }}" name="access_notes" rows="3">{{ $fieldValue('access_notes', $member->access_notes) }}</textarea>
        @if ($fieldError('access_notes'))
            <div class="invalid-feedback">{{ $fieldError('access_notes') }}</div>
        @endif
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save family access</button>
    </div>
</div>
