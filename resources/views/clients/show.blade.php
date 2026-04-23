@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Clients', 'url' => route('clients.index')],
        ['label' => $client->fullName()],
    ]" />
@endsection

@section('content')
    @php
        $statusBadge = $client->status === 'active' ? 'text-bg-success' : 'text-bg-secondary';
        $onboardingBadge = [
            'onboarding' => 'text-bg-info',
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'declined' => 'text-bg-danger',
        ][$client->onboarding_status] ?? 'text-bg-secondary';

        $assessmentBadgeClass = fn (string $status): string => [
            'onboarding' => 'text-bg-info',
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'declined' => 'text-bg-danger',
        ][$status] ?? 'text-bg-secondary';

        $carePlanBadgeClass = fn (string $status): string => [
            'draft' => 'text-bg-warning',
            'active' => 'text-bg-success',
            'inactive' => 'text-bg-secondary',
        ][$status] ?? 'text-bg-secondary';

        $sectionRows = function (?object $section, array $fields): array {
            if ($section === null) {
                return [];
            }

            return collect($fields)
                ->map(fn (string $label, string $field): array => [$label, $section->{$field}])
                ->filter(fn (array $row): bool => filled($row[1]))
                ->all();
        };

        $assessmentSections = function ($assessment) use ($sectionRows): array {
            return [
                'master' => [
                    'label' => 'Master',
                    'rows' => [
                        ['Assessment date', $assessment->assessment_date?->format('Y-m-d')],
                        ['Assessor', $assessment->assessor_name],
                        ['Type', ucfirst($assessment->assessment_type)],
                        ['Risk', $assessment->overall_risk_level ? ucfirst($assessment->overall_risk_level) : null],
                        ['Submitted', $assessment->submitted_at?->format('Y-m-d H:i')],
                        ['Reviewed', $assessment->reviewed_at?->format('Y-m-d H:i')],
                        ['Summary', $assessment->overall_summary],
                        ['Recommendations', $assessment->recommendations],
                        ['Review notes', $assessment->review_notes],
                    ],
                ],
                'needs' => ['label' => 'Needs', 'rows' => $sectionRows($assessment->needs, ['physical_needs' => 'Physical needs', 'psychological_needs' => 'Psychological needs', 'social_needs' => 'Social needs', 'spiritual_needs' => 'Spiritual needs', 'environmental_needs' => 'Environmental needs', 'priority_needs' => 'Priority needs', 'notes' => 'Notes'])],
                'functional' => ['label' => 'Functional', 'rows' => $sectionRows($assessment->functional, ['mobility_status' => 'Mobility', 'bathing_ability' => 'Bathing', 'dressing_ability' => 'Dressing', 'eating_ability' => 'Eating', 'toileting_ability' => 'Toileting', 'transferring_ability' => 'Transferring', 'continence_status' => 'Continence', 'independence_level' => 'Independence', 'notes' => 'Notes'])],
                'medical' => ['label' => 'Medical', 'rows' => $sectionRows($assessment->medical, ['diagnoses' => 'Diagnoses', 'medical_conditions' => 'Conditions', 'medications' => 'Medications', 'allergies' => 'Allergies', 'vital_signs' => 'Vital signs', 'gp_details' => 'GP details', 'medication_support_needed' => 'Medication support needed', 'notes' => 'Notes'])],
                'capacity' => ['label' => 'Capacity', 'rows' => $sectionRows($assessment->mentalCapacity, ['decision_type' => 'Decision type', 'understands_information' => 'Understands information', 'retains_information' => 'Retains information', 'weighs_information' => 'Weighs information', 'communicates_decision' => 'Communicates decision', 'capacity_outcome' => 'Outcome', 'best_interest_decision' => 'Best-interest decision', 'imca_involved' => 'IMCA involved', 'dols_lps_status' => 'DoLS/LPS status', 'notes' => 'Notes'])],
                'risk' => ['label' => 'Risk', 'rows' => $sectionRows($assessment->risk, ['falls_risk' => 'Falls', 'pressure_ulcer_risk' => 'Pressure ulcer', 'manual_handling_risk' => 'Manual handling', 'environmental_risk' => 'Environmental', 'behaviour_risk' => 'Behaviour', 'safeguarding_risk' => 'Safeguarding', 'control_measures' => 'Control measures', 'notes' => 'Notes'])],
                'communication' => ['label' => 'Communication', 'rows' => $sectionRows($assessment->communication, ['preferred_language' => 'Preferred language', 'communication_method' => 'Method', 'hearing_impairment' => 'Hearing impairment', 'vision_impairment' => 'Vision impairment', 'speech_difficulty' => 'Speech difficulty', 'interpreter_required' => 'Interpreter required', 'communication_aids' => 'Aids', 'notes' => 'Notes'])],
                'equality' => ['label' => 'Equality', 'rows' => $sectionRows($assessment->equality, ['gender' => 'Gender', 'ethnicity' => 'Ethnicity', 'religion' => 'Religion', 'disability_status' => 'Disability', 'sexual_orientation' => 'Sexual orientation', 'cultural_needs' => 'Cultural needs', 'reasonable_adjustments' => 'Reasonable adjustments', 'notes' => 'Notes'])],
                'social' => ['label' => 'Social', 'rows' => $sectionRows($assessment->social, ['living_arrangements' => 'Living arrangements', 'family_support' => 'Family support', 'social_isolation_risk' => 'Isolation risk', 'community_engagement' => 'Community engagement', 'employment_status' => 'Employment status', 'financial_concerns' => 'Financial concerns', 'notes' => 'Notes'])],
                'environmental' => ['label' => 'Environmental', 'rows' => $sectionRows($assessment->environmental, ['home_condition' => 'Home condition', 'safety_hazards' => 'Safety hazards', 'accessibility' => 'Accessibility', 'equipment_needed' => 'Equipment needed', 'fire_risk' => 'Fire risk', 'cleanliness_level' => 'Cleanliness', 'notes' => 'Notes'])],
            ];
        };

        $carePlanSections = function ($plan): array {
            return [
                'plan' => [
                    'label' => 'Plan',
                    'rows' => [
                        ['Title', $plan->title],
                        ['Type', $plan->plan_type],
                        ['Care level', $plan->care_level],
                        ['Visit frequency', $plan->visit_frequency],
                        ['Start date', $plan->start_date?->format('Y-m-d')],
                        ['Review date', $plan->review_date?->format('Y-m-d')],
                        ['Review frequency', $plan->review_frequency],
                        ['Status', ucfirst($plan->status)],
                        ['Goals and outcomes', $plan->care_goals],
                    ],
                ],
                'support' => [
                    'label' => 'Support',
                    'rows' => [
                        ['Personal care level', $plan->personal_care_level],
                        ['Personal care notes', $plan->personal_care_support],
                        ['Mobility level', $plan->mobility_level],
                        ['Mobility notes', $plan->mobility_support],
                        ['Nutrition support level', $plan->nutrition_support_level],
                        ['Nutrition and hydration notes', $plan->nutrition_hydration_support],
                        ['Medication support level', $plan->medication_support_level],
                        ['Medication notes', $plan->medication_support],
                        ['Communication support level', $plan->communication_support_level],
                        ['Communication notes', $plan->communication_support],
                    ],
                ],
                'safety' => [
                    'label' => 'Safety and review',
                    'rows' => [
                        ['Risk level', $plan->risk_level],
                        ['Risk management', $plan->risk_management],
                        ['Preferences and routines', $plan->preferences_routines],
                        ['Escalation instructions', $plan->escalation_instructions],
                        ['Review notes', $plan->review_notes],
                    ],
                ],
            ];
        };
    @endphp

    <x-page-header title="{{ $client->fullName() }}" description="View client details, assessment history, and care plans retained for audit.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('clients.index') }}"><i class="fa-solid fa-arrow-left me-1"></i>Clients</a>
                <a class="btn btn-primary" href="{{ route('clients.assessments.edit', $client) }}"><i class="fa-solid fa-list-check me-1"></i>{{ $client->assessment?->status === 'onboarding' ? 'Resume assessment' : 'New assessment version' }}</a>
            </div>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <ul class="nav nav-tabs" id="clientShowTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="client-details-tab" data-bs-toggle="tab" data-bs-target="#client-details-pane" type="button" role="tab" aria-controls="client-details-pane" aria-selected="true">Client details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="client-assessments-tab" data-bs-toggle="tab" data-bs-target="#client-assessments-pane" type="button" role="tab" aria-controls="client-assessments-pane" aria-selected="false">Assessments</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="client-care-plans-tab" data-bs-toggle="tab" data-bs-target="#client-care-plans-pane" type="button" role="tab" aria-controls="client-care-plans-pane" aria-selected="false">Care Plans</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="client-visits-tab" data-bs-toggle="tab" data-bs-target="#client-visits-pane" type="button" role="tab" aria-controls="client-visits-pane" aria-selected="false">Visits</button>
                </li>
            </ul>

            <div class="tab-content pt-4" id="clientShowTabsContent">
                <div class="tab-pane fade show active" id="client-details-pane" role="tabpanel" aria-labelledby="client-details-tab" tabindex="0">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="border rounded p-4 h-100">
                                <p class="section-kicker mb-2">Client</p>
                                <h2 class="h4 fw-bold mb-3">{{ $client->fullName() }}</h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge {{ $statusBadge }}">{{ ucfirst($client->status) }}</span>
                                    <span class="badge {{ $onboardingBadge }}">{{ ucfirst($client->onboarding_status ?: 'onboarding') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="border rounded p-4 h-100">
                                <dl class="row mb-0 g-3">
                                    <dt class="col-md-4 text-secondary">Home</dt>
                                    <dd class="col-md-8 mb-0">{{ $client->home->name }}</dd>
                                    <dt class="col-md-4 text-secondary">Date of birth</dt>
                                    <dd class="col-md-8 mb-0">{{ $client->date_of_birth?->format('Y-m-d') ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-4 text-secondary">Gender</dt>
                                    <dd class="col-md-8 mb-0">{{ $client->gender ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-4 text-secondary">Phone</dt>
                                    <dd class="col-md-8 mb-0">{{ $client->phone ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-4 text-secondary">Email</dt>
                                    <dd class="col-md-8 mb-0">{{ $client->email ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-4 text-secondary">Address</dt>
                                    <dd class="col-md-8 mb-0">{{ $client->address ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-4 text-secondary">Emergency contact</dt>
                                    <dd class="col-md-8 mb-0">
                                        {{ $client->emergency_contact_name ?: 'Not recorded' }}
                                        @if ($client->emergency_contact_phone)
                                            <span class="d-block text-secondary">{{ $client->emergency_contact_phone }}</span>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    @if ($client->review_notes)
                        <div class="alert alert-warning mt-4 mb-0">
                            <strong>Latest review notes:</strong> {{ $client->review_notes }}
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="client-assessments-pane" role="tabpanel" aria-labelledby="client-assessments-tab" tabindex="0" data-assessment-history>
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Assessment History</p>
                            <h2 class="h4 fw-bold mb-0">Assessment Versions</h2>
                        </div>
                        <span class="text-secondary fw-semibold">{{ $client->assessments->count() }} total versions</span>
                    </div>

                    @if ($client->assessments->isEmpty())
                        <div class="alert alert-info mb-0">No assessment versions have been created yet.</div>
                    @else
                        <div class="table-responsive mb-4">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Version</th>
                                        <th>Status</th>
                                        <th>Assessment date</th>
                                        <th>Submitted</th>
                                        <th>Reviewed</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($client->assessments as $assessment)
                                        <tr>
                                            <td class="fw-bold">Version {{ $assessment->version }}</td>
                                            <td><span class="badge {{ $assessmentBadgeClass($assessment->status) }}">{{ ucfirst($assessment->status) }}</span></td>
                                            <td>{{ $assessment->assessment_date?->format('Y-m-d') ?: 'Not recorded' }}</td>
                                            <td>{{ $assessment->submitted_at?->format('Y-m-d H:i') ?: 'Not submitted' }}</td>
                                            <td>{{ $assessment->reviewed_at?->format('Y-m-d H:i') ?: 'Not reviewed' }}</td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-action {{ $loop->first ? 'btn-action-primary' : '' }}" type="button" data-assessment-version-target="assessment-version-{{ $assessment->id }}">
                                                    <i class="fa-solid fa-eye"></i>Show
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @foreach ($client->assessments as $assessment)
                            @php($sections = $assessmentSections($assessment))
                            <section class="assessment-version-panel {{ $loop->first ? '' : 'd-none' }}" id="assessment-version-{{ $assessment->id }}">
                                <div class="border rounded p-3 p-md-4">
                                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                        <div>
                                            <h3 class="h5 fw-bold mb-1">Version {{ $assessment->version }}</h3>
                                            <p class="text-secondary mb-0">{{ $assessment->assessment_date?->format('Y-m-d') ?: 'No assessment date' }}</p>
                                        </div>
                                        <span class="badge {{ $assessmentBadgeClass($assessment->status) }} align-self-start">{{ ucfirst($assessment->status) }}</span>
                                    </div>

                                    <ul class="nav nav-tabs flex-nowrap overflow-auto" id="assessmentStepTabs{{ $assessment->id }}" role="tablist">
                                        @foreach ($sections as $key => $section)
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="assessment-{{ $assessment->id }}-{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#assessment-{{ $assessment->id }}-{{ $key }}-pane" type="button" role="tab" aria-controls="assessment-{{ $assessment->id }}-{{ $key }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                    {{ $section['label'] }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="tab-content pt-4" id="assessmentStepTabsContent{{ $assessment->id }}">
                                        @foreach ($sections as $key => $section)
                                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="assessment-{{ $assessment->id }}-{{ $key }}-pane" role="tabpanel" aria-labelledby="assessment-{{ $assessment->id }}-{{ $key }}-tab" tabindex="0">
                                                @php($rows = collect($section['rows'])->filter(fn (array $row): bool => filled($row[1]))->all())
                                                @if ($rows === [])
                                                    <p class="text-secondary mb-0">No details recorded for this step.</p>
                                                @else
                                                    <dl class="row g-3 mb-0">
                                                        @foreach ($rows as [$label, $value])
                                                            <dt class="col-md-4 text-secondary">{{ $label }}</dt>
                                                            <dd class="col-md-8 mb-0">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</dd>
                                                        @endforeach
                                                    </dl>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    @endif
                </div>

                <div class="tab-pane fade" id="client-care-plans-pane" role="tabpanel" aria-labelledby="client-care-plans-tab" tabindex="0">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Care Planning</p>
                            <h2 class="h4 fw-bold mb-0">Care Plan Details</h2>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <span class="text-secondary fw-semibold">{{ $client->carePlans->count() }} total plans</span>
                            @if (auth()->user()->hasPermission('care_plans.manage') && $client->status === 'active')
                                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createClientCarePlanModal"><i class="fa-solid fa-plus me-1"></i>New care plan</button>
                                <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('care-plans.index') }}"><i class="fa-solid fa-clipboard-list"></i>Manage care plans</a>
                            @endif
                        </div>
                    </div>

                    @if ($client->carePlans->isEmpty())
                        <div class="alert alert-info mb-0">No care plans have been created for this client yet.</div>
                    @else
                        <div class="table-responsive mb-4">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Care plan</th>
                                        <th>Type</th>
                                        <th>Care level</th>
                                        <th>Risk</th>
                                        <th>Review</th>
                                        <th>Status</th>
                                        @if (auth()->user()->hasPermission('care_plans.manage') && $client->status === 'active')
                                            <th class="text-end">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($client->carePlans as $plan)
                                        <tr>
                                            <td>
                                                <p class="fw-bold mb-0">{{ $plan->title }}</p>
                                                <p class="text-secondary mb-0">Started {{ $plan->start_date?->format('Y-m-d') ?: 'not recorded' }}</p>
                                            </td>
                                            <td>{{ $plan->plan_type ?: 'Not set' }}</td>
                                            <td>{{ $plan->care_level ?: 'Not set' }}</td>
                                            <td>{{ $plan->risk_level ?: 'Not set' }}</td>
                                            <td>
                                                <p class="mb-0">{{ $plan->review_date?->format('Y-m-d') ?: 'Not scheduled' }}</p>
                                                <p class="text-secondary mb-0">{{ $plan->review_frequency ?: '' }}</p>
                                            </td>
                                            <td><span class="badge {{ $carePlanBadgeClass($plan->status) }}">{{ ucfirst($plan->status) }}</span></td>
                                            @if (auth()->user()->hasPermission('care_plans.manage') && $client->status === 'active')
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editClientCarePlanModal{{ $plan->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-grid gap-3">
                            @foreach ($client->carePlans as $plan)
                                @php($sections = $carePlanSections($plan))
                                <section class="border rounded p-3 p-md-4">
                                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                        <div>
                                            <h3 class="h5 fw-bold mb-1">{{ $plan->title }}</h3>
                                            <p class="text-secondary mb-0">{{ $plan->plan_type }} plan from {{ $plan->start_date?->format('Y-m-d') ?: 'not recorded' }}</p>
                                        </div>
                                        <span class="badge {{ $carePlanBadgeClass($plan->status) }} align-self-start">{{ ucfirst($plan->status) }}</span>
                                    </div>

                                    <ul class="nav nav-tabs flex-nowrap overflow-auto" id="carePlanTabs{{ $plan->id }}" role="tablist">
                                        @foreach ($sections as $key => $section)
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="care-plan-{{ $plan->id }}-{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#care-plan-{{ $plan->id }}-{{ $key }}-pane" type="button" role="tab" aria-controls="care-plan-{{ $plan->id }}-{{ $key }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                    {{ $section['label'] }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="tab-content pt-4" id="carePlanTabsContent{{ $plan->id }}">
                                        @foreach ($sections as $key => $section)
                                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="care-plan-{{ $plan->id }}-{{ $key }}-pane" role="tabpanel" aria-labelledby="care-plan-{{ $plan->id }}-{{ $key }}-tab" tabindex="0">
                                                @php($rows = collect($section['rows'])->filter(fn (array $row): bool => filled($row[1]))->all())
                                                @if ($rows === [])
                                                    <p class="text-secondary mb-0">No details recorded for this section.</p>
                                                @else
                                                    <dl class="row g-3 mb-0">
                                                        @foreach ($rows as [$label, $value])
                                                            <dt class="col-md-4 text-secondary">{{ $label }}</dt>
                                                            <dd class="col-md-8 mb-0">{{ $value }}</dd>
                                                        @endforeach
                                                    </dl>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="client-visits-pane" role="tabpanel" aria-labelledby="client-visits-tab" tabindex="0">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Visit Schedule</p>
                            <h2 class="h4 fw-bold mb-0">Scheduled and completed visits</h2>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <span class="text-secondary fw-semibold">{{ $client->visits->count() }} total visits</span>
                            @if (auth()->user()->hasPermission('clients.manage') && $client->status === 'active')
                                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createClientVisitModal"><i class="fa-solid fa-plus me-1"></i>Book visit</button>
                                <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('visits.index') }}"><i class="fa-solid fa-calendar-check"></i>Manage visits</a>
                            @endif
                        </div>
                    </div>

                    @if ($client->visits->isEmpty())
                        <div class="alert alert-info mb-0">No visits have been scheduled for this client yet. The visit model is now available for scheduling and EVV linkage.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Visit</th>
                                        <th>Schedule</th>
                                        <th>Worker</th>
                                        <th>Care plan</th>
                                        <th>Status</th>
                                        <th>Check in</th>
                                        <th>Check out</th>
                                        @if (auth()->user()->hasPermission('clients.manage') && $client->status === 'active')
                                            <th class="text-end">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($client->visits as $visit)
                                        <tr>
                                            <td>
                                                <p class="fw-bold mb-0">{{ $visit->title }}</p>
                                                @if ($visit->notes)
                                                    <p class="text-secondary mb-0">{{ $visit->notes }}</p>
                                                @endif
                                            </td>
                                            <td>{{ $visit->durationLabel() }}</td>
                                            <td>{{ $visit->assignedWorker?->name ?: 'Unassigned' }}</td>
                                            <td>{{ $visit->carePlan?->title ?: 'Not linked' }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $visit->status === 'completed' ? 'success' : ($visit->status === 'in_progress' ? 'warning' : 'secondary') }}">
                                                    {{ str($visit->status)->replace('_', ' ')->headline() }}
                                                </span>
                                            </td>
                                            <td>{{ $visit->check_in_at?->format('Y-m-d H:i') ?: 'Not checked in' }}</td>
                                            <td>{{ $visit->check_out_at?->format('Y-m-d H:i') ?: 'Not checked out' }}</td>
                                            @if (auth()->user()->hasPermission('clients.manage') && $client->status === 'active')
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editClientVisitModal{{ $visit->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasPermission('care_plans.manage') && $client->status === 'active')
        <div class="modal fade" id="createClientCarePlanModal" tabindex="-1" aria-labelledby="createClientCarePlanModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('care-plans.store') }}">
                    @csrf
                    <input type="hidden" name="return_to_client_id" value="{{ $client->id }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="createClientCarePlanModalLabel">New care plan for {{ $client->fullName() }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('care-plans.partials.form', ['carePlan' => $newCarePlan, 'clients' => $carePlanClients, 'submitLabel' => 'Create care plan', 'formId' => 'client_show_create'])
                    </div>
                </form>
            </div>
        </div>

        @foreach ($client->carePlans as $editPlan)
            <div class="modal fade" id="editClientCarePlanModal{{ $editPlan->id }}" tabindex="-1" aria-labelledby="editClientCarePlanModalLabel{{ $editPlan->id }}" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <form class="modal-content" method="POST" action="{{ route('care-plans.update', $editPlan) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="return_to_client_id" value="{{ $client->id }}">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="editClientCarePlanModalLabel{{ $editPlan->id }}">Edit {{ $editPlan->title }}</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('care-plans.partials.form', ['carePlan' => $editPlan, 'clients' => $carePlanClients, 'submitLabel' => 'Update care plan', 'formId' => 'client_show_edit_'.$editPlan->id])
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif

    @if (auth()->user()->hasPermission('clients.manage') && $client->status === 'active')
        <div class="modal fade" id="createClientVisitModal" tabindex="-1" aria-labelledby="createClientVisitModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('visits.store') }}">
                    @csrf
                    <input type="hidden" name="return_to_client_id" value="{{ $client->id }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="createClientVisitModalLabel">Book visit for {{ $client->fullName() }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('visits.partials.form', ['visit' => $newVisit, 'clients' => $visitClients, 'workers' => $visitWorkers, 'submitLabel' => 'Book visit', 'formId' => 'client_show_create_visit'])
                    </div>
                </form>
            </div>
        </div>

        @foreach ($client->visits as $editVisit)
            <div class="modal fade" id="editClientVisitModal{{ $editVisit->id }}" tabindex="-1" aria-labelledby="editClientVisitModalLabel{{ $editVisit->id }}" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <form class="modal-content" method="POST" action="{{ route('visits.update', $editVisit) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="return_to_client_id" value="{{ $client->id }}">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="editClientVisitModalLabel{{ $editVisit->id }}">Edit {{ $editVisit->title }}</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('visits.partials.form', ['visit' => $editVisit, 'clients' => $visitClients, 'workers' => $visitWorkers, 'submitLabel' => 'Update visit', 'formId' => 'client_show_edit_visit_'.$editVisit->id])
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
@endsection
