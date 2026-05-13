@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="brand-kicker mb-1">Family access</p>
                <h1 class="h3 fw-bold mb-2">Welcome, {{ $familyMember->name }}</h1>
                <p class="text-secondary mb-0">You are viewing the information shared for {{ $familyMember->client->fullName() }}.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Log out</button>
            </form>
        </div>

        <div class="alert alert-info">
            Family access is permission-based. Only information approved by the care team is shown here.
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-3">Client profile</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Client</dt>
                            <dd class="col-sm-8">{{ $familyMember->client->fullName() }}</dd>
                            <dt class="col-sm-4">Home</dt>
                            <dd class="col-sm-8">{{ $familyMember->client->home?->name ?? 'Not assigned' }}</dd>
                            <dt class="col-sm-4">Relationship</dt>
                            <dd class="col-sm-8">{{ $familyMember->relationship ?: 'Not recorded' }}</dd>
                            <dt class="col-sm-4">Last login</dt>
                            <dd class="col-sm-8">{{ $familyMember->last_login_at?->format('d/m/Y H:i') ?? 'Not recorded' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            @if ($carePlanSummary)
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Care plan summary</h2>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Plan</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['title'] }}</dd>
                                <dt class="col-sm-4">Care level</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['care_level'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Visit frequency</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['visit_frequency'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Review date</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['review_date']?->format('d/m/Y') ?? 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Risk level</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['risk_level'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Goals</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['care_goals'] ?: 'Not recorded' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            @endif

            @if ($medicationSummary)
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Medication summary</h2>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Support needed</dt>
                                <dd class="col-sm-8">{{ $medicationSummary['support_needed'] ? 'Yes' : 'No' }}</dd>
                                <dt class="col-sm-4">Support summary</dt>
                                <dd class="col-sm-8">{{ $medicationSummary['support_summary'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Allergies</dt>
                                <dd class="col-sm-8">{{ $medicationSummary['allergies'] ?: 'Not recorded' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            @endif

            @if ($appointments->isNotEmpty())
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Appointments and visits</h2>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Visit</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Care plan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($appointments as $visit)
                                            <tr>
                                                <td>{{ $visit->title }}</td>
                                                <td>{{ $visit->durationLabel() }}</td>
                                                <td><span class="badge text-bg-light border">{{ str($visit->status)->replace('_', ' ')->title() }}</span></td>
                                                <td>{{ $visit->carePlan?->title ?? 'Not linked' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($visitNotes->isNotEmpty())
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Visit notes shared by staff</h2>
                            <div class="vstack gap-3">
                                @foreach ($visitNotes as $visit)
                                    <div class="border rounded p-3">
                                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                            <p class="fw-semibold mb-0">{{ $visit->title }}</p>
                                            <span class="text-secondary small">{{ $visit->durationLabel() }}</span>
                                        </div>
                                        <p class="mb-0">{{ $visit->notes }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($incidentNotifications->isNotEmpty())
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Incident notifications</h2>
                            <div class="vstack gap-3">
                                @foreach ($incidentNotifications as $incident)
                                    <div class="border rounded p-3">
                                        <p class="fw-semibold mb-1">{{ data_get($incident->metadata, 'category', 'Incident update') }}</p>
                                        <p class="text-secondary mb-0">A manager-approved incident notification is available. Reported {{ $incident->created_at->format('d/m/Y H:i') }}.</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (! $carePlanSummary && ! $medicationSummary && $appointments->isEmpty() && $visitNotes->isEmpty() && $incidentNotifications->isEmpty())
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-2">No shared updates yet</h2>
                            <p class="text-secondary mb-0">The care team has not shared any care updates, visits, medication summaries, or incident notifications with this family account yet.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($familyMember->canAccess('can_view_invoices') || $familyMember->canAccess('can_view_staff_messages') || $familyMember->canAccess('can_view_shared_documents'))
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Other shared areas</h2>
                            <div class="row g-3">
                                @if ($familyMember->canAccess('can_view_invoices'))
                                    <div class="col-md-4">
                                        <div class="border rounded p-3 h-100">
                                            <p class="fw-semibold mb-1">Invoices</p>
                                            <p class="text-secondary mb-0">No invoice statements are currently shared.</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($familyMember->canAccess('can_view_staff_messages'))
                                    <div class="col-md-4">
                                        <div class="border rounded p-3 h-100">
                                            <p class="fw-semibold mb-1">Messages from staff</p>
                                            <p class="text-secondary mb-0">No staff messages are currently shared.</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($familyMember->canAccess('can_view_shared_documents'))
                                    <div class="col-md-4">
                                        <div class="border rounded p-3 h-100">
                                            <p class="fw-semibold mb-1">Documents</p>
                                            <p class="text-secondary mb-0">No shared documents are currently available.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
