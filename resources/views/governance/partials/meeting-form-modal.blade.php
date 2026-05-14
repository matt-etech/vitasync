<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                        <h3 class="form-section-title">Governance Meeting</h3>
                        <p class="form-section-description">Record agenda, attendance, minutes, outcomes, and linked follow-up actions.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Meeting type</label>
                            <input class="form-control" name="meeting_type" value="{{ old('meeting_type', $meeting?->meeting_type) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled for</label>
                            <input class="form-control" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', optional($meeting?->scheduled_at)->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chair</label>
                            <select class="form-select" name="chair_user_id">
                                <option value="">Unassigned</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((int) old('chair_user_id', $meeting?->chair_user_id) === (int) $owner->id)>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                @foreach ($meetingStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $meeting?->status ?? 'scheduled') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Attendees</label>
                            <textarea class="form-control" name="attendees" rows="2">{{ old('attendees', $meeting?->attendees) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Agenda</label>
                            <textarea class="form-control" name="agenda" rows="3" required>{{ old('agenda', $meeting?->agenda) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Minutes</label>
                            <textarea class="form-control" name="minutes" rows="4">{{ old('minutes', $meeting?->minutes) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Outcome evidence</label>
                            <textarea class="form-control" name="outcome" rows="3">{{ old('outcome', $meeting?->outcome) }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">{{ $method === 'POST' ? 'Create meeting' : 'Save meeting' }}</button>
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
