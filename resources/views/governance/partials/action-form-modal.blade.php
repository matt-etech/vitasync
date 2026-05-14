<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ $actionUrl }}">
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
                        <h3 class="form-section-title">Tracked Action</h3>
                        <p class="form-section-description">Assign ownership, due date, and closure evidence for the governance task.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Complaint link</label>
                            <select class="form-select" name="governance_complaint_id">
                                <option value="">No complaint link</option>
                                @foreach ($complaints as $complaint)
                                    <option value="{{ $complaint->id }}" @selected((int) old('governance_complaint_id', $trackedAction?->governance_complaint_id) === (int) $complaint->id)>{{ $complaint->reference }} - {{ str($complaint->summary)->limit(45) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GDPR case link</label>
                            <select class="form-select" name="gdpr_case_id">
                                <option value="">No GDPR link</option>
                                @foreach ($gdprCases as $gdprCase)
                                    <option value="{{ $gdprCase->id }}" @selected((int) old('gdpr_case_id', $trackedAction?->gdpr_case_id) === (int) $gdprCase->id)>{{ $gdprCase->reference }} - {{ $gdprTypes[$gdprCase->request_type] ?? $gdprCase->request_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Policy link</label>
                            <select class="form-select" name="governance_policy_id">
                                <option value="">No policy link</option>
                                @foreach ($policies as $policy)
                                    <option value="{{ $policy->id }}" @selected((int) old('governance_policy_id', $trackedAction?->governance_policy_id) === (int) $policy->id)>{{ $policy->reference }} - {{ str($policy->title)->limit(45) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meeting link</label>
                            <select class="form-select" name="governance_meeting_id">
                                <option value="">No meeting link</option>
                                @foreach ($meetings as $meeting)
                                    <option value="{{ $meeting->id }}" @selected((int) old('governance_meeting_id', $trackedAction?->governance_meeting_id) === (int) $meeting->id)>{{ $meeting->reference }} - {{ str($meeting->meeting_type)->limit(45) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Title</label>
                            <input class="form-control" name="title" value="{{ old('title', $trackedAction?->title) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Owner</label>
                            <select class="form-select" name="owner_user_id">
                                <option value="">Unassigned</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((int) old('owner_user_id', $trackedAction?->owner_user_id) === (int) $owner->id)>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority" required>
                                @foreach ($actionPriorities as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', $trackedAction?->priority ?? 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                @foreach ($actionStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $trackedAction?->status ?? 'open') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due date</label>
                            <input class="form-control" name="due_at" type="date" value="{{ old('due_at', optional($trackedAction?->due_at)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3">{{ old('description', $trackedAction?->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Outcome evidence</label>
                            <textarea class="form-control" name="outcome" rows="3">{{ old('outcome', $trackedAction?->outcome) }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">{{ $method === 'POST' ? 'Create action' : 'Save action' }}</button>
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
