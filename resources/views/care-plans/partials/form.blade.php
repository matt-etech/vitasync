<div>
    @php
        $useOldInput = $useOldInput ?? true;
        $fieldValue = fn (string $field, mixed $fallback = null) => $useOldInput ? old($field, $fallback) : $fallback;
        $fieldHasError = fn (string $field): bool => $useOldInput && $errors->has($field);
        $fieldError = fn (string $field): ?string => $fieldHasError($field) ? $errors->first($field) : null;
        $selectOptions = [
            'plan_type' => ['Initial', 'Review', 'Temporary', 'End of life'],
            'care_level' => ['Low', 'Medium', 'High', 'Complex'],
            'visit_frequency' => ['Daily', 'Multiple daily', 'Weekly', 'As needed', 'Live-in'],
            'review_frequency' => ['Weekly', 'Monthly', 'Quarterly', 'Six monthly', 'Annual'],
            'personal_care_level' => ['Independent', 'Prompting', 'Partial support', 'Full support'],
            'mobility_level' => ['Independent', 'Prompting', 'One person assist', 'Two person assist', 'Hoist'],
            'nutrition_support_level' => ['Independent', 'Prompting', 'Meal preparation', 'Assisted eating', 'Specialist diet'],
            'medication_support_level' => ['None', 'Prompting', 'Administered by staff', 'Monitored', 'Managed by clinician'],
            'communication_support_level' => ['Verbal', 'Non-verbal', 'Hearing support', 'Vision support', 'Interpreter required'],
            'risk_level' => ['Low', 'Medium', 'High', 'Critical'],
        ];
    @endphp

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Plan Details</h2>
            <p class="form-section-description">Select the client, plan type, planned support level, and review cadence.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="client_id_{{ $formId }}">Client</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('client_id') ? 'is-invalid' : '' }}" id="client_id_{{ $formId }}" name="client_id" required>
                    <option value="">Select client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected((int) $fieldValue('client_id', $carePlan->client_id) === (int) $client->id)>{{ $client->fullName() }} - {{ $client->home->name }}</option>
                    @endforeach
                </select>
                @if ($fieldError('client_id'))
                    <div class="invalid-feedback">{{ $fieldError('client_id') }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label" for="title_{{ $formId }}">Care plan title</label>
                <input class="form-control focus-ring-brand {{ $fieldHasError('title') ? 'is-invalid' : '' }}" id="title_{{ $formId }}" name="title" value="{{ $fieldValue('title', $carePlan->title) }}" required>
                @if ($fieldError('title'))
                    <div class="invalid-feedback">{{ $fieldError('title') }}</div>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label" for="plan_type_{{ $formId }}">Plan type</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('plan_type') ? 'is-invalid' : '' }}" id="plan_type_{{ $formId }}" name="plan_type" required>
                    @foreach ($selectOptions['plan_type'] as $option)
                        <option value="{{ $option }}" @selected($fieldValue('plan_type', $carePlan->plan_type ?: 'Initial') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @if ($fieldError('plan_type'))
                    <div class="invalid-feedback">{{ $fieldError('plan_type') }}</div>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label" for="care_level_{{ $formId }}">Care level</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('care_level') ? 'is-invalid' : '' }}" id="care_level_{{ $formId }}" name="care_level">
                    <option value="">Select level</option>
                    @foreach ($selectOptions['care_level'] as $option)
                        <option value="{{ $option }}" @selected($fieldValue('care_level', $carePlan->care_level) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @if ($fieldError('care_level'))
                    <div class="invalid-feedback">{{ $fieldError('care_level') }}</div>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label" for="visit_frequency_{{ $formId }}">Visit frequency</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('visit_frequency') ? 'is-invalid' : '' }}" id="visit_frequency_{{ $formId }}" name="visit_frequency">
                    <option value="">Select frequency</option>
                    @foreach ($selectOptions['visit_frequency'] as $option)
                        <option value="{{ $option }}" @selected($fieldValue('visit_frequency', $carePlan->visit_frequency) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @if ($fieldError('visit_frequency'))
                    <div class="invalid-feedback">{{ $fieldError('visit_frequency') }}</div>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status_{{ $formId }}">Status</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('status') ? 'is-invalid' : '' }}" id="status_{{ $formId }}" name="status" required>
                    @foreach (['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected($fieldValue('status', $carePlan->status ?: 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($fieldError('status'))
                    <div class="invalid-feedback">{{ $fieldError('status') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="start_date_{{ $formId }}">Start date</label>
                <input class="form-control focus-ring-brand {{ $fieldHasError('start_date') ? 'is-invalid' : '' }}" id="start_date_{{ $formId }}" name="start_date" type="date" value="{{ $fieldValue('start_date', $carePlan->start_date?->format('Y-m-d') ?: now()->toDateString()) }}" required>
                @if ($fieldError('start_date'))
                    <div class="invalid-feedback">{{ $fieldError('start_date') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="review_date_{{ $formId }}">Review date</label>
                <input class="form-control focus-ring-brand {{ $fieldHasError('review_date') ? 'is-invalid' : '' }}" id="review_date_{{ $formId }}" name="review_date" type="date" value="{{ $fieldValue('review_date', $carePlan->review_date?->format('Y-m-d')) }}">
                @if ($fieldError('review_date'))
                    <div class="invalid-feedback">{{ $fieldError('review_date') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="review_frequency_{{ $formId }}">Review frequency</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('review_frequency') ? 'is-invalid' : '' }}" id="review_frequency_{{ $formId }}" name="review_frequency">
                    <option value="">Select frequency</option>
                    @foreach ($selectOptions['review_frequency'] as $option)
                        <option value="{{ $option }}" @selected($fieldValue('review_frequency', $carePlan->review_frequency) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @if ($fieldError('review_frequency'))
                    <div class="invalid-feedback">{{ $fieldError('review_frequency') }}</div>
                @endif
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Care And Support</h2>
            <p class="form-section-description">Use controlled support levels first, then add practical notes for delivery.</p>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="care_goals_{{ $formId }}">Care goals and outcomes</label>
                <textarea class="form-control focus-ring-brand {{ $fieldHasError('care_goals') ? 'is-invalid' : '' }}" id="care_goals_{{ $formId }}" name="care_goals" rows="3">{{ $fieldValue('care_goals', $carePlan->care_goals) }}</textarea>
                @if ($fieldError('care_goals'))
                    <div class="invalid-feedback">{{ $fieldError('care_goals') }}</div>
                @endif
            </div>
            @foreach ([
                'personal_care' => ['level' => 'personal_care_level', 'notes' => 'personal_care_support', 'label' => 'Personal care', 'placeholder' => 'Select personal care level'],
                'mobility' => ['level' => 'mobility_level', 'notes' => 'mobility_support', 'label' => 'Mobility and transfers', 'placeholder' => 'Select mobility level'],
                'nutrition' => ['level' => 'nutrition_support_level', 'notes' => 'nutrition_hydration_support', 'label' => 'Nutrition and hydration', 'placeholder' => 'Select nutrition support'],
                'medication' => ['level' => 'medication_support_level', 'notes' => 'medication_support', 'label' => 'Medication support', 'placeholder' => 'Select medication support'],
                'communication' => ['level' => 'communication_support_level', 'notes' => 'communication_support', 'label' => 'Communication support', 'placeholder' => 'Select communication support'],
            ] as $group)
                <div class="col-md-6">
                    <label class="form-label" for="{{ $group['level'] }}_{{ $formId }}">{{ $group['label'] }} level</label>
                    <select class="form-select focus-ring-brand {{ $fieldHasError($group['level']) ? 'is-invalid' : '' }}" id="{{ $group['level'] }}_{{ $formId }}" name="{{ $group['level'] }}">
                        <option value="">{{ $group['placeholder'] }}</option>
                        @foreach ($selectOptions[$group['level']] as $option)
                            <option value="{{ $option }}" @selected($fieldValue($group['level'], $carePlan->{$group['level']}) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @if ($fieldError($group['level']))
                        <div class="invalid-feedback">{{ $fieldError($group['level']) }}</div>
                    @endif
                    <label class="form-label mt-3" for="{{ $group['notes'] }}_{{ $formId }}">{{ $group['label'] }} notes</label>
                    <textarea class="form-control focus-ring-brand {{ $fieldHasError($group['notes']) ? 'is-invalid' : '' }}" id="{{ $group['notes'] }}_{{ $formId }}" name="{{ $group['notes'] }}" rows="3">{{ $fieldValue($group['notes'], $carePlan->{$group['notes']}) }}</textarea>
                    @if ($fieldError($group['notes']))
                        <div class="invalid-feedback">{{ $fieldError($group['notes']) }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Safety And Review</h2>
            <p class="form-section-description">Record risk controls, preferences, escalation steps, and review notes.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="risk_level_{{ $formId }}">Risk level</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('risk_level') ? 'is-invalid' : '' }}" id="risk_level_{{ $formId }}" name="risk_level">
                    <option value="">Select risk level</option>
                    @foreach ($selectOptions['risk_level'] as $option)
                        <option value="{{ $option }}" @selected($fieldValue('risk_level', $carePlan->risk_level) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @if ($fieldError('risk_level'))
                    <div class="invalid-feedback">{{ $fieldError('risk_level') }}</div>
                @endif
            </div>
            @foreach ([
                'risk_management' => 'Risk management',
                'preferences_routines' => 'Preferences and routines',
                'escalation_instructions' => 'Escalation instructions',
                'review_notes' => 'Review notes',
            ] as $field => $label)
                <div class="col-md-6">
                    <label class="form-label" for="{{ $field }}_{{ $formId }}">{{ $label }}</label>
                    <textarea class="form-control focus-ring-brand {{ $fieldHasError($field) ? 'is-invalid' : '' }}" id="{{ $field }}_{{ $formId }}" name="{{ $field }}" rows="3">{{ $fieldValue($field, $carePlan->{$field}) }}</textarea>
                    @if ($fieldError($field))
                        <div class="invalid-feedback">{{ $fieldError($field) }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
