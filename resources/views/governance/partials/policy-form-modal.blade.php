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
                        <h3 class="form-section-title">Policy Library</h3>
                        <p class="form-section-description">Track policy ownership, review dates, approval state, and inspection-ready evidence.</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Policy title</label>
                            <input class="form-control" name="title" value="{{ old('title', $policy?->title) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <input class="form-control" name="category" value="{{ old('category', $policy?->category) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Version</label>
                            <input class="form-control" name="version" value="{{ old('version', $policy?->version ?? '1.0') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                @foreach ($policyStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $policy?->status ?? 'draft') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Review due</label>
                            <input class="form-control" name="review_due_at" type="date" value="{{ old('review_due_at', optional($policy?->review_due_at)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Owner</label>
                            <select class="form-select" name="owner_user_id">
                                <option value="">Unassigned</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((int) old('owner_user_id', $policy?->owner_user_id) === (int) $owner->id)>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Summary and evidence notes</label>
                            <textarea class="form-control" name="summary" rows="4" required>{{ old('summary', $policy?->summary) }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">{{ $method === 'POST' ? 'Create policy' : 'Save policy' }}</button>
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
