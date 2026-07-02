<div>
    @php
        $useOldInput = $useOldInput ?? true;
        $fieldValue = fn (string $field, mixed $fallback = null) => $useOldInput ? old($field, $fallback) : $fallback;
        $fieldHasError = fn (string $field): bool => $useOldInput && $errors->has($field);
        $fieldError = fn (string $field): ?string => $fieldHasError($field) ? $errors->first($field) : null;
        $minimumVisitDateTime = now()->format('Y-m-d\\TH:i');
        $currentStatus = $fieldValue('status', $visit->status ?: 'scheduled');
        $enforceFutureWindow = $enforceFutureWindow ?? in_array($currentStatus, ['scheduled', 'in_progress'], true);
        $statuses = [
            'scheduled' => 'Scheduled',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'missed' => 'Missed',
            'cancelled' => 'Cancelled',
        ];
    @endphp

    <section class="form-section">
        <div class="form-section-header">
            <h2 class="form-section-title">Visit Details</h2>
            <p class="form-section-description">Link the visit to a client and care plan, then set the scheduled delivery window.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="client_id_{{ $formId }}">Client</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('client_id') ? 'is-invalid' : '' }}" id="client_id_{{ $formId }}" name="client_id" required>
                    <option value="">Select client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" data-home-id="{{ $client->home_id }}" @selected((int) $fieldValue('client_id', $visit->client_id) === (int) $client->id)>{{ $client->fullName() }} - {{ $client->home->name }}</option>
                    @endforeach
                </select>
                @if ($fieldError('client_id'))
                    <div class="invalid-feedback">{{ $fieldError('client_id') }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label" for="care_plan_id_{{ $formId }}">Care plan</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('care_plan_id') ? 'is-invalid' : '' }}" id="care_plan_id_{{ $formId }}" name="care_plan_id">
                    <option value="">No linked care plan</option>
                    @foreach ($clients as $client)
                        @foreach ($client->carePlans as $carePlan)
                            <option value="{{ $carePlan->id }}" @selected((int) $fieldValue('care_plan_id', $visit->care_plan_id) === (int) $carePlan->id)>{{ $carePlan->title }} - {{ $client->fullName() }}</option>
                        @endforeach
                    @endforeach
                </select>
                @if ($fieldError('care_plan_id'))
                    <div class="invalid-feedback">{{ $fieldError('care_plan_id') }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label" for="title_{{ $formId }}">Visit title</label>
                <input class="form-control focus-ring-brand {{ $fieldHasError('title') ? 'is-invalid' : '' }}" id="title_{{ $formId }}" name="title" value="{{ $fieldValue('title', $visit->title) }}" required>
                @if ($fieldError('title'))
                    <div class="invalid-feedback">{{ $fieldError('title') }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label" for="assigned_user_id_{{ $formId }}">Assigned worker</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('assigned_user_id') ? 'is-invalid' : '' }}" id="assigned_user_id_{{ $formId }}" name="assigned_user_id" data-visit-worker-select data-current-visit-id="{{ $visit->id }}">
                    <option value="">Unassigned</option>
                    @foreach ($workers as $worker)
                        @php
                            $profile = $worker->carerProfile;
                            $complianceFailures = $profile?->criticalValidationFailures() ?? ['Approved carer profile is required.'];

                            if ($profile?->status !== \App\Models\CarerProfile::STATUS_APPROVED) {
                                $complianceFailures[] = 'Onboarding must be approved.';
                            }

                            if ($profile?->account_status !== 'active') {
                                $complianceFailures[] = 'Account status must be active.';
                            }

                            if ($profile?->dbs_expiry_date?->isPast()) {
                                $complianceFailures[] = 'DBS certificate is expired.';
                            }

                            foreach (($profile?->trainingRecords ?? collect()) as $trainingRecord) {
                                if ($trainingRecord->expiry_date?->isPast()) {
                                    $complianceFailures[] = "{$trainingRecord->training_name} training is expired.";
                                }
                            }

                            $complianceFailures = collect($complianceFailures)->unique()->values();
                            $existingVisits = $worker->assignedVisits
                                ->whereNotIn('status', ['cancelled', 'missed'])
                                ->map(fn ($assignedVisit) => [
                                    'id' => $assignedVisit->id,
                                    'start' => $assignedVisit->scheduled_start_at?->toIso8601String(),
                                    'end' => $assignedVisit->scheduled_end_at?->toIso8601String(),
                                ])
                                ->values();
                        @endphp
                        <option
                            value="{{ $worker->id }}"
                            data-home-id="{{ $profile?->assigned_home_id ?: $worker->home_id }}"
                            data-compliance-ready="{{ $complianceFailures->isEmpty() ? '1' : '0' }}"
                            data-compliance-reasons='@json($complianceFailures)'
                            data-shift-preference="{{ $profile?->shift_preference }}"
                            data-max-weekly-hours="{{ $profile?->max_weekly_hours }}"
                            data-existing-visits='@json($existingVisits)'
                            @selected((int) $fieldValue('assigned_user_id', $visit->assigned_user_id) === (int) $worker->id)
                        >{{ $worker->name }}{{ $worker->home ? ' - '.$worker->home->name : '' }}</option>
                    @endforeach
                </select>
                @if ($fieldError('assigned_user_id'))
                    <div class="invalid-feedback">{{ $fieldError('assigned_user_id') }}</div>
                @endif
                <div class="form-text" data-visit-worker-guidance role="status" aria-live="polite">
                    Select a client and visit window to check worker compliance and availability.
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="scheduled_start_at_{{ $formId }}">Scheduled start</label>
                <input class="form-control focus-ring-brand {{ $fieldHasError('scheduled_start_at') ? 'is-invalid' : '' }}" id="scheduled_start_at_{{ $formId }}" name="scheduled_start_at" type="datetime-local" value="{{ $fieldValue('scheduled_start_at', optional($visit->scheduled_start_at)->format('Y-m-d\\TH:i') ?: now()->addMinutes(15)->startOfMinute()->format('Y-m-d\\TH:i')) }}" @if($enforceFutureWindow) min="{{ $minimumVisitDateTime }}" @endif required>
                @if ($fieldError('scheduled_start_at'))
                    <div class="invalid-feedback">{{ $fieldError('scheduled_start_at') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="scheduled_end_at_{{ $formId }}">Scheduled end</label>
                <input class="form-control focus-ring-brand {{ $fieldHasError('scheduled_end_at') ? 'is-invalid' : '' }}" id="scheduled_end_at_{{ $formId }}" name="scheduled_end_at" type="datetime-local" value="{{ $fieldValue('scheduled_end_at', optional($visit->scheduled_end_at)->format('Y-m-d\\TH:i') ?: now()->addMinutes(75)->startOfMinute()->format('Y-m-d\\TH:i')) }}" @if($enforceFutureWindow) min="{{ $minimumVisitDateTime }}" @endif required>
                @if ($fieldError('scheduled_end_at'))
                    <div class="invalid-feedback">{{ $fieldError('scheduled_end_at') }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status_{{ $formId }}">Status</label>
                <select class="form-select focus-ring-brand {{ $fieldHasError('status') ? 'is-invalid' : '' }}" id="status_{{ $formId }}" name="status" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($fieldError('status'))
                    <div class="invalid-feedback">{{ $fieldError('status') }}</div>
                @endif
            </div>
            <div class="col-12">
                <label class="form-label" for="notes_{{ $formId }}">Visit notes</label>
                <textarea class="form-control focus-ring-brand {{ $fieldHasError('notes') ? 'is-invalid' : '' }}" id="notes_{{ $formId }}" name="notes" rows="4">{{ $fieldValue('notes', $visit->notes) }}</textarea>
                @if ($fieldError('notes'))
                    <div class="invalid-feedback">{{ $fieldError('notes') }}</div>
                @endif
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
