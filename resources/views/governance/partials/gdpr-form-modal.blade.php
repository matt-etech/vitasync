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
                        <h3 class="form-section-title">GDPR Case</h3>
                        <p class="form-section-description">Track SAR, breach, correction, deletion, and DPIA work with response deadlines and outcome evidence.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Requester name</label>
                            <input class="form-control" name="requester_name" value="{{ old('requester_name', $gdprCase?->requester_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Requester contact</label>
                            <input class="form-control" name="requester_contact" value="{{ old('requester_contact', $gdprCase?->requester_contact) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Linked client</label>
                            <select class="form-select" name="client_id">
                                <option value="">Not linked</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((int) old('client_id', $gdprCase?->client_id) === (int) $client->id)>{{ $client->fullName() }}{{ $client->home ? ' - '.$client->home->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Request type</label>
                            <select class="form-select" name="request_type" required>
                                @foreach ($gdprTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('request_type', $gdprCase?->request_type ?? 'sar') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Risk level</label>
                            <select class="form-select" name="risk_level" required>
                                @foreach ($riskLevels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('risk_level', $gdprCase?->risk_level ?? 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                @foreach ($gdprStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $gdprCase?->status ?? 'open') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Owner</label>
                            <select class="form-select" name="owner_user_id">
                                <option value="">Unassigned</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((int) old('owner_user_id', $gdprCase?->owner_user_id) === (int) $owner->id)>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Received at</label>
                            <input class="form-control" name="received_at" type="datetime-local" value="{{ old('received_at', optional($gdprCase?->received_at)->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Response due</label>
                            <input class="form-control" name="response_due_at" type="date" value="{{ old('response_due_at', optional($gdprCase?->response_due_at)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Summary</label>
                            <textarea class="form-control" name="summary" rows="4" required>{{ old('summary', $gdprCase?->summary) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Outcome evidence</label>
                            <textarea class="form-control" name="outcome" rows="4">{{ old('outcome', $gdprCase?->outcome) }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">{{ $method === 'POST' ? 'Open GDPR case' : 'Save GDPR case' }}</button>
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
