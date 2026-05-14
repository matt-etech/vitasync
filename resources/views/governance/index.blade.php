@extends('layouts.app')

@php
    $statusBadge = static function (string $status): string {
        return match ($status) {
            'closed', 'resolved', 'responded', 'completed' => 'text-bg-success',
            'investigating', 'reviewing', 'reported', 'in_progress' => 'text-bg-warning',
            'cancelled' => 'text-bg-secondary',
            default => 'text-bg-info',
        };
    };

    $riskBadge = static function (string $level): string {
        return match ($level) {
            'critical' => 'text-bg-danger',
            'high' => 'text-bg-warning',
            'low' => 'text-bg-success',
            default => 'text-bg-info',
        };
    };
@endphp

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Governance'],
        ['label' => 'Workbench'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Governance Workbench" description="Manage complaints, GDPR requests and breaches, and the action evidence needed for inspection readiness.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createComplaintModal"><i class="fa-solid fa-plus me-1"></i>New complaint</button>
                <button class="btn btn-action" type="button" data-bs-toggle="modal" data-bs-target="#createGdprCaseModal"><i class="fa-solid fa-shield-halved"></i>New GDPR case</button>
                <button class="btn btn-action" type="button" data-bs-toggle="modal" data-bs-target="#createPolicyModal"><i class="fa-solid fa-book"></i>New policy</button>
                <button class="btn btn-action" type="button" data-bs-toggle="modal" data-bs-target="#createMeetingModal"><i class="fa-solid fa-users-line"></i>New meeting</button>
                <button class="btn btn-action" type="button" data-bs-toggle="modal" data-bs-target="#createActionModal"><i class="fa-solid fa-list-check"></i>New action</button>
            </div>
        </x-slot:action>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Complaints</p>
                    <h2 class="h3 fw-bold mb-0">{{ $complaints->whereNotIn('status', ['resolved', 'closed'])->count() }}</h2>
                    <p class="text-secondary mb-0">Open or investigating complaint cases</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">GDPR</p>
                    <h2 class="h3 fw-bold mb-0">{{ $gdprCases->whereNotIn('status', ['responded', 'closed'])->count() }}</h2>
                    <p class="text-secondary mb-0">Open SAR, breach, deletion, correction, or DPIA cases</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Actions</p>
                    <h2 class="h3 fw-bold mb-0">{{ $actions->whereNotIn('status', ['completed', 'cancelled'])->count() }}</h2>
                    <p class="text-secondary mb-0">Tracked governance actions awaiting outcome evidence</p>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs vitasync-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="complaints-tab" data-bs-toggle="tab" data-bs-target="#complaints-panel" type="button" role="tab">Complaints</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="gdpr-tab" data-bs-toggle="tab" data-bs-target="#gdpr-panel" type="button" role="tab">GDPR cases</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="policies-tab" data-bs-toggle="tab" data-bs-target="#policies-panel" type="button" role="tab">Policies</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="meetings-tab" data-bs-toggle="tab" data-bs-target="#meetings-panel" type="button" role="tab">Meetings</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions-panel" type="button" role="tab">Actions</button>
        </li>
    </ul>

    <div class="tab-content">
        <section class="tab-pane fade show active" id="complaints-panel" role="tabpanel" aria-labelledby="complaints-tab">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Complainant</th>
                                <th>Client</th>
                                <th>Risk</th>
                                <th>Status</th>
                                <th>Owner</th>
                                <th>Actions</th>
                                <th class="no-export">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($complaints as $complaint)
                                <tr>
                                    <td>
                                        <p class="fw-semibold mb-0">{{ $complaint->reference }}</p>
                                        <p class="text-secondary mb-0">{{ $complaint->received_at->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td>
                                        <p class="fw-semibold mb-0">{{ $complaint->complainant_name }}</p>
                                        <p class="text-secondary mb-0">{{ $complaint->category }}</p>
                                    </td>
                                    <td>{{ $complaint->client?->fullName() ?? 'Not linked' }}</td>
                                    <td><span class="badge {{ $riskBadge($complaint->severity) }}">{{ $complaintSeverities[$complaint->severity] ?? $complaint->severity }}</span></td>
                                    <td><span class="badge {{ $statusBadge($complaint->status) }}">{{ $complaintStatuses[$complaint->status] ?? $complaint->status }}</span></td>
                                    <td>{{ $complaint->owner?->name ?? 'Unassigned' }}</td>
                                    <td>{{ $complaint->actions->whereNotIn('status', ['completed', 'cancelled'])->count() }} open</td>
                                    <td><button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editComplaintModal{{ $complaint->id }}"><i class="fa-solid fa-pen"></i>Manage</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-secondary py-4">No complaints recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="gdpr-panel" role="tabpanel" aria-labelledby="gdpr-tab">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Requester</th>
                                <th>Type</th>
                                <th>Client</th>
                                <th>Risk</th>
                                <th>Status</th>
                                <th>Due</th>
                                <th class="no-export">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($gdprCases as $gdprCase)
                                <tr>
                                    <td><p class="fw-semibold mb-0">{{ $gdprCase->reference }}</p><p class="text-secondary mb-0">{{ $gdprCase->received_at->format('d/m/Y H:i') }}</p></td>
                                    <td>{{ $gdprCase->requester_name }}</td>
                                    <td>{{ $gdprTypes[$gdprCase->request_type] ?? $gdprCase->request_type }}</td>
                                    <td>{{ $gdprCase->client?->fullName() ?? 'Not linked' }}</td>
                                    <td><span class="badge {{ $riskBadge($gdprCase->risk_level) }}">{{ $riskLevels[$gdprCase->risk_level] ?? $gdprCase->risk_level }}</span></td>
                                    <td><span class="badge {{ $statusBadge($gdprCase->status) }}">{{ $gdprStatuses[$gdprCase->status] ?? $gdprCase->status }}</span></td>
                                    <td>{{ $gdprCase->response_due_at?->format('d/m/Y') ?? 'Not set' }}</td>
                                    <td><button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editGdprCaseModal{{ $gdprCase->id }}"><i class="fa-solid fa-pen"></i>Manage</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-secondary py-4">No GDPR cases recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="policies-panel" role="tabpanel" aria-labelledby="policies-tab">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Policy</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Owner</th>
                                <th>Review due</th>
                                <th class="no-export">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($policies as $policy)
                                <tr>
                                    <td>{{ $policy->reference }}</td>
                                    <td><p class="fw-semibold mb-0">{{ $policy->title }}</p><p class="text-secondary mb-0">{{ $policy->category }}</p></td>
                                    <td>{{ $policy->version }}</td>
                                    <td><span class="badge {{ $statusBadge($policy->status) }}">{{ $policyStatuses[$policy->status] ?? $policy->status }}</span></td>
                                    <td>{{ $policy->owner?->name ?? 'Unassigned' }}</td>
                                    <td>{{ $policy->review_due_at?->format('d/m/Y') ?? 'Not set' }}</td>
                                    <td><button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editPolicyModal{{ $policy->id }}"><i class="fa-solid fa-pen"></i>Manage</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-secondary py-4">No policies recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="meetings-panel" role="tabpanel" aria-labelledby="meetings-tab">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Meeting</th>
                                <th>Status</th>
                                <th>Chair</th>
                                <th>Scheduled</th>
                                <th>Actions</th>
                                <th class="no-export">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($meetings as $meeting)
                                <tr>
                                    <td>{{ $meeting->reference }}</td>
                                    <td><p class="fw-semibold mb-0">{{ $meeting->meeting_type }}</p><p class="text-secondary mb-0">{{ str($meeting->agenda)->limit(80) }}</p></td>
                                    <td><span class="badge {{ $statusBadge($meeting->status) }}">{{ $meetingStatuses[$meeting->status] ?? $meeting->status }}</span></td>
                                    <td>{{ $meeting->chair?->name ?? 'Unassigned' }}</td>
                                    <td>{{ $meeting->scheduled_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $meeting->actions->whereNotIn('status', ['completed', 'cancelled'])->count() }} open</td>
                                    <td><button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editMeetingModal{{ $meeting->id }}"><i class="fa-solid fa-pen"></i>Manage</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-secondary py-4">No governance meetings recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane fade" id="actions-panel" role="tabpanel" aria-labelledby="actions-tab">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th>Linked case</th>
                                <th>Owner</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due</th>
                                <th class="no-export">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($actions as $trackedAction)
                                <tr>
                                    <td><p class="fw-semibold mb-0">{{ $trackedAction->title }}</p><p class="text-secondary mb-0">{{ str($trackedAction->description)->limit(90) }}</p></td>
                                    <td>{{ $trackedAction->parentLabel() }}</td>
                                    <td>{{ $trackedAction->owner?->name ?? 'Unassigned' }}</td>
                                    <td><span class="badge {{ $riskBadge($trackedAction->priority) }}">{{ $actionPriorities[$trackedAction->priority] ?? $trackedAction->priority }}</span></td>
                                    <td><span class="badge {{ $statusBadge($trackedAction->status) }}">{{ $actionStatuses[$trackedAction->status] ?? $trackedAction->status }}</span></td>
                                    <td>{{ $trackedAction->due_at?->format('d/m/Y') ?? 'Not set' }}</td>
                                    <td><button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editActionModal{{ $trackedAction->id }}"><i class="fa-solid fa-pen"></i>Manage</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-secondary py-4">No governance actions recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include('governance.partials.complaint-form-modal', ['modalId' => 'createComplaintModal', 'title' => 'New complaint', 'action' => route('governance.complaints.store'), 'method' => 'POST', 'complaint' => null])
    @include('governance.partials.gdpr-form-modal', ['modalId' => 'createGdprCaseModal', 'title' => 'New GDPR case', 'action' => route('governance.gdpr-cases.store'), 'method' => 'POST', 'gdprCase' => null])
    @include('governance.partials.policy-form-modal', ['modalId' => 'createPolicyModal', 'title' => 'New policy', 'action' => route('governance.policies.store'), 'method' => 'POST', 'policy' => null])
    @include('governance.partials.meeting-form-modal', ['modalId' => 'createMeetingModal', 'title' => 'New meeting', 'action' => route('governance.meetings.store'), 'method' => 'POST', 'meeting' => null])
    @include('governance.partials.action-form-modal', ['modalId' => 'createActionModal', 'title' => 'New governance action', 'actionUrl' => route('governance.actions.store'), 'method' => 'POST', 'trackedAction' => null])

    @foreach ($complaints as $complaint)
        @include('governance.partials.complaint-form-modal', ['modalId' => 'editComplaintModal'.$complaint->id, 'title' => 'Manage '.$complaint->reference, 'action' => route('governance.complaints.update', $complaint), 'method' => 'PUT', 'complaint' => $complaint])
    @endforeach

    @foreach ($gdprCases as $gdprCase)
        @include('governance.partials.gdpr-form-modal', ['modalId' => 'editGdprCaseModal'.$gdprCase->id, 'title' => 'Manage '.$gdprCase->reference, 'action' => route('governance.gdpr-cases.update', $gdprCase), 'method' => 'PUT', 'gdprCase' => $gdprCase])
    @endforeach

    @foreach ($policies as $policy)
        @include('governance.partials.policy-form-modal', ['modalId' => 'editPolicyModal'.$policy->id, 'title' => 'Manage '.$policy->reference, 'action' => route('governance.policies.update', $policy), 'method' => 'PUT', 'policy' => $policy])
    @endforeach

    @foreach ($meetings as $meeting)
        @include('governance.partials.meeting-form-modal', ['modalId' => 'editMeetingModal'.$meeting->id, 'title' => 'Manage '.$meeting->reference, 'action' => route('governance.meetings.update', $meeting), 'method' => 'PUT', 'meeting' => $meeting])
    @endforeach

    @foreach ($actions as $trackedAction)
        @include('governance.partials.action-form-modal', ['modalId' => 'editActionModal'.$trackedAction->id, 'title' => 'Manage action', 'actionUrl' => route('governance.actions.update', $trackedAction), 'method' => 'PUT', 'trackedAction' => $trackedAction])
    @endforeach
@endsection
