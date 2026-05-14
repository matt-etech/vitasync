<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <div class="modal-header">
                <h2 class="modal-title h5">{{ $title }}</h2>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <section class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">Complaint Intake</h3>
                        <p class="form-section-description">Record the concern, owner, risk, and response deadline. Closing requires outcome evidence.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Complainant name</label>
                            <input class="form-control" name="complainant_name" value="{{ old('complainant_name', $complaint?->complainant_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Complainant contact</label>
                            <input class="form-control" name="complainant_contact" value="{{ old('complainant_contact', $complaint?->complainant_contact) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Linked client</label>
                            <select class="form-select" name="client_id">
                                <option value="">Not linked</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((int) old('client_id', $complaint?->client_id) === (int) $client->id)>{{ $client->fullName() }}{{ $client->home ? ' - '.$client->home->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Source</label>
                            <input class="form-control" name="source" value="{{ old('source', $complaint?->source ?? 'Family') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <input class="form-control" name="category" value="{{ old('category', $complaint?->category ?? 'Care quality') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Severity</label>
                            <select class="form-select" name="severity" required>
                                @foreach ($complaintSeverities as $value => $label)
                                    <option value="{{ $value }}" @selected(old('severity', $complaint?->severity ?? 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                @foreach ($complaintStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $complaint?->status ?? 'open') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Owner</label>
                            <select class="form-select" name="owner_user_id">
                                <option value="">Unassigned</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((int) old('owner_user_id', $complaint?->owner_user_id) === (int) $owner->id)>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Received at</label>
                            <input class="form-control" name="received_at" type="datetime-local" value="{{ old('received_at', optional($complaint?->received_at)->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Response due</label>
                            <input class="form-control" name="due_at" type="date" value="{{ old('due_at', optional($complaint?->due_at)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Summary</label>
                            <textarea class="form-control" name="summary" rows="4" required>{{ old('summary', $complaint?->summary) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Outcome evidence</label>
                            <textarea class="form-control" name="outcome" rows="4">{{ old('outcome', $complaint?->outcome) }}</textarea>
                            <p class="form-text">Required before the case can be considered inspection-ready.</p>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">{{ $method === 'POST' ? 'Open complaint' : 'Save complaint' }}</button>
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
