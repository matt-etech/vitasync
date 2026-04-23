<div>
    @php
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
                <select class="form-select focus-ring-brand" id="client_id_{{ $formId }}" name="client_id" required>
                    <option value="">Select client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected((int) old('client_id', $visit->client_id) === (int) $client->id)>{{ $client->fullName() }} - {{ $client->home->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="care_plan_id_{{ $formId }}">Care plan</label>
                <select class="form-select focus-ring-brand" id="care_plan_id_{{ $formId }}" name="care_plan_id">
                    <option value="">No linked care plan</option>
                    @foreach ($clients as $client)
                        @foreach ($client->carePlans as $carePlan)
                            <option value="{{ $carePlan->id }}" @selected((int) old('care_plan_id', $visit->care_plan_id) === (int) $carePlan->id)>{{ $carePlan->title }} - {{ $client->fullName() }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="title_{{ $formId }}">Visit title</label>
                <input class="form-control focus-ring-brand" id="title_{{ $formId }}" name="title" value="{{ old('title', $visit->title) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="assigned_user_id_{{ $formId }}">Assigned worker</label>
                <select class="form-select focus-ring-brand" id="assigned_user_id_{{ $formId }}" name="assigned_user_id">
                    <option value="">Unassigned</option>
                    @foreach ($workers as $worker)
                        <option value="{{ $worker->id }}" @selected((int) old('assigned_user_id', $visit->assigned_user_id) === (int) $worker->id)>{{ $worker->name }}{{ $worker->home ? ' - '.$worker->home->name : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="scheduled_start_at_{{ $formId }}">Scheduled start</label>
                <input class="form-control focus-ring-brand" id="scheduled_start_at_{{ $formId }}" name="scheduled_start_at" type="datetime-local" value="{{ old('scheduled_start_at', optional($visit->scheduled_start_at)->format('Y-m-d\\TH:i') ?: now()->startOfHour()->format('Y-m-d\\TH:i')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="scheduled_end_at_{{ $formId }}">Scheduled end</label>
                <input class="form-control focus-ring-brand" id="scheduled_end_at_{{ $formId }}" name="scheduled_end_at" type="datetime-local" value="{{ old('scheduled_end_at', optional($visit->scheduled_end_at)->format('Y-m-d\\TH:i') ?: now()->startOfHour()->addHour()->format('Y-m-d\\TH:i')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status_{{ $formId }}">Status</label>
                <select class="form-select focus-ring-brand" id="status_{{ $formId }}" name="status" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $visit->status ?: 'scheduled') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes_{{ $formId }}">Visit notes</label>
                <textarea class="form-control focus-ring-brand" id="notes_{{ $formId }}" name="notes" rows="4">{{ old('notes', $visit->notes) }}</textarea>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary fw-semibold" type="submit">{{ $submitLabel }}</button>
        <button class="btn btn-outline-secondary fw-semibold" type="button" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
