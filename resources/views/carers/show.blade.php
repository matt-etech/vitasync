@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Carers', 'url' => route('carers.index')],
        ['label' => $carer->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="{{ $carer->name }}" description="Review carer profile details and the visits currently assigned to this worker.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('carers.index') }}"><i class="fa-solid fa-arrow-left me-1"></i>Carers</a>
                <a class="btn btn-action btn-action-primary" href="{{ route('carers.assessments.edit', $carer) }}"><i class="fa-solid fa-list-check"></i>Assessment</a>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#editCarerModal"><i class="fa-solid fa-pen me-1"></i>Edit carer</button>
            </div>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <ul class="nav nav-tabs" id="carerShowTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="carer-profile-tab" data-bs-toggle="tab" data-bs-target="#carer-profile-pane" type="button" role="tab" aria-controls="carer-profile-pane" aria-selected="true">Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="carer-visits-tab" data-bs-toggle="tab" data-bs-target="#carer-visits-pane" type="button" role="tab" aria-controls="carer-visits-pane" aria-selected="false">Visits</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="carer-medication-tab" data-bs-toggle="tab" data-bs-target="#carer-medication-pane" type="button" role="tab" aria-controls="carer-medication-pane" aria-selected="false">Medication administration</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="carer-family-messages-tab" data-bs-toggle="tab" data-bs-target="#carer-family-messages-pane" type="button" role="tab" aria-controls="carer-family-messages-pane" aria-selected="false">Family messages</button>
                </li>
            </ul>

            <div class="tab-content pt-4" id="carerShowTabsContent">
                <div class="tab-pane fade show active" id="carer-profile-pane" role="tabpanel" aria-labelledby="carer-profile-tab" tabindex="0">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="border rounded p-4 h-100">
                                <p class="section-kicker mb-2">Carer</p>
                                <h2 class="h4 fw-bold mb-3">{{ $carer->name }}</h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge text-bg-{{ $carer->is_active ? 'success' : 'secondary' }}">{{ $carer->is_active ? 'Active' : 'Inactive' }}</span>
                                    <span class="badge text-bg-light border">Carer role</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="border rounded p-4 h-100">
                                <dl class="row mb-0 g-3">
                                    <dt class="col-md-4 text-secondary">Email</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->email }}</dd>
                                    <dt class="col-md-4 text-secondary">Login</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->is_active ? 'Enabled' : 'Disabled' }}</dd>
                                    <dt class="col-md-4 text-secondary">System role</dt>
                                    <dd class="col-md-8 mb-0">Carer</dd>
                                    <dt class="col-md-4 text-secondary">Home</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->home?->name ?: 'Unassigned' }}</dd>
                                    <dt class="col-md-4 text-secondary">Job title</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->job_title ?: 'Carer' }}</dd>
                                    <dt class="col-md-4 text-secondary">Phone</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->phone ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-4 text-secondary">Assigned visits</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->assignedVisits->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Identity & Legal</p>
                                        <h2 class="h4 fw-bold mb-0">Right to work profile</h2>
                                    </div>
                                    @if ($carer->carerProfile?->id_document_path)
                                        <span class="badge text-bg-light border align-self-start">ID document stored privately</span>
                                    @endif
                                </div>

                                @if ($carer->carerProfile?->legal_name)
                                    <dl class="row mb-0 g-3">
                                        <dt class="col-md-3 text-secondary">Legal name</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->legal_name ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Date of birth</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->date_of_birth?->format('d/m/Y') ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">National Insurance</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->national_insurance_number ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Photo ID type</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->photoIdTypeLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">ID document number</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->id_document_number ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Right to work</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->rightToWorkStatusLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">Visa status</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->visaStatusLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">ID upload</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->id_document_path ? basename($carer->carerProfile->id_document_path) : 'Not uploaded' }}</dd>
                                    </dl>
                                @else
                                    <div class="alert alert-warning d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>Identity and legal assessment has not been completed.</span>
                                        <a class="btn btn-sm btn-warning fw-semibold" href="{{ route('carers.assessments.edit', $carer) }}">Continue assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Contact & Emergency</p>
                                        <h2 class="h4 fw-bold mb-0">Address and escalation details</h2>
                                    </div>
                                </div>

                                @if ($carer->carerProfile?->address_line_1)
                                    <dl class="row mb-0 g-3">
                                        <dt class="col-md-3 text-secondary">Address line 1</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->address_line_1 }}</dd>
                                        <dt class="col-md-3 text-secondary">Address line 2</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->address_line_2 ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">City</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->city ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Postcode</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->postcode ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Phone</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->contact_phone ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Email</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->contact_email ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Emergency contact</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->emergency_contact_name ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Emergency phone</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->emergency_contact_phone ?: 'Not recorded' }}</dd>
                                    </dl>
                                @else
                                    <div class="alert alert-warning d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>Contact and emergency assessment has not been completed.</span>
                                        <a class="btn btn-sm btn-warning fw-semibold" href="{{ route('carers.assessments.edit', $carer) }}">Continue assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Employment & Role</p>
                                        <h2 class="h4 fw-bold mb-0">Role assignment</h2>
                                    </div>
                                </div>

                                @if ($carer->carerProfile?->job_title)
                                    <dl class="row mb-0 g-3">
                                        <dt class="col-md-3 text-secondary">Job title</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->jobTitleLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">Employment type</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->employmentTypeLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">Start date</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->start_date?->format('d/m/Y') ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Assigned home</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->assignedHome?->name ?: 'Not recorded' }}</dd>
                                    </dl>
                                @else
                                    <div class="alert alert-warning d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>Employment and role assessment has not been completed.</span>
                                        <a class="btn btn-sm btn-warning fw-semibold" href="{{ route('carers.assessments.edit', $carer) }}">Continue assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Safeguarding & Compliance</p>
                                        <h2 class="h4 fw-bold mb-0">DBS and safeguarding</h2>
                                    </div>
                                </div>

                                @if ($carer->carerProfile?->dbs_check_status)
                                    <dl class="row mb-0 g-3">
                                        <dt class="col-md-3 text-secondary">DBS status</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->dbsCheckStatusLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">DBS certificate</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->dbs_certificate_number ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">DBS expiry</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->dbs_expiry_date?->format('d/m/Y') ?: 'Not recorded' }}</dd>
                                        <dt class="col-md-3 text-secondary">Safeguarding completed</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->safeguardingTrainingCompletedLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">Last safeguarding date</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->last_safeguarding_training_date?->format('d/m/Y') ?: 'Not recorded' }}</dd>
                                    </dl>
                                @else
                                    <div class="alert alert-warning d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>Safeguarding and compliance assessment has not been completed.</span>
                                        <a class="btn btn-sm btn-warning fw-semibold" href="{{ route('carers.assessments.edit', $carer) }}">Continue assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Training & Qualifications</p>
                                        <h2 class="h4 fw-bold mb-0">Mandatory training checklist</h2>
                                    </div>
                                </div>

                                @if ($carer->carerProfile?->trainingRecords?->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Training</th>
                                                    <th>Status</th>
                                                    <th>Expiry</th>
                                                    <th>Certificate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (\App\Models\CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName)
                                                    @php
                                                        $record = $carer->carerProfile->trainingRecords->firstWhere('training_key', $trainingKey);
                                                    @endphp
                                                    <tr>
                                                        <td class="fw-semibold">{{ $trainingName }}</td>
                                                        <td>{{ $record?->statusLabel() ?: 'Not recorded' }}</td>
                                                        <td>{{ $record?->expiry_date?->format('d/m/Y') ?: 'Not recorded' }}</td>
                                                        <td>{{ $record?->certificate_path ? basename($record->certificate_path) : 'Not uploaded' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-warning d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>Training and qualifications assessment has not been completed.</span>
                                        <a class="btn btn-sm btn-warning fw-semibold" href="{{ route('carers.assessments.edit', $carer) }}">Continue assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Health & Fitness to Work</p>
                                        <h2 class="h4 fw-bold mb-0">Work readiness</h2>
                                    </div>
                                </div>

                                @if ($carer->carerProfile?->occupational_health_clearance)
                                    <dl class="row mb-0 g-3">
                                        <dt class="col-md-3 text-secondary">Occupational health</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->occupationalHealthClearanceLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">Immunisation status</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->immunisationStatusLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">Fit-to-work declaration</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->fit_to_work_declaration ? 'Confirmed' : 'Not confirmed' }}</dd>
                                    </dl>
                                @else
                                    <div class="alert alert-warning d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>Health and fitness to work assessment has not been completed.</span>
                                        <a class="btn btn-sm btn-warning fw-semibold" href="{{ route('carers.assessments.edit', $carer) }}">Continue assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Skills & Competencies</p>
                                        <h2 class="h4 fw-bold mb-0">Specialist support</h2>
                                    </div>
                                </div>

                                @if ($carer->carerProfile && (($carer->carerProfile->skills ?: []) !== [] || ($carer->carerProfile->languages ?: []) !== []))
                                    <dl class="row mb-0 g-3">
                                        <dt class="col-md-3 text-secondary">Skills</dt>
                                        <dd class="col-md-9 mb-0">
                                            @forelse ($carer->carerProfile->skillsLabels() as $skill)
                                                <span class="badge text-bg-light border me-1 mb-1">{{ $skill }}</span>
                                            @empty
                                                Not recorded
                                            @endforelse
                                        </dd>
                                        <dt class="col-md-3 text-secondary">Languages</dt>
                                        <dd class="col-md-9 mb-0">
                                            @forelse ($carer->carerProfile->languageLabels() as $language)
                                                <span class="badge text-bg-light border me-1 mb-1">{{ $language }}</span>
                                            @empty
                                                Not recorded
                                            @endforelse
                                        </dd>
                                    </dl>
                                @else
                                    <div class="alert alert-info d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>No skills or languages have been selected yet.</span>
                                        <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('carers.assessments.edit', $carer) }}">Update assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">Availability & Scheduling</p>
                                        <h2 class="h4 fw-bold mb-0">Capacity and shift pattern</h2>
                                    </div>
                                </div>

                                @if ($carer->carerProfile?->availability_pattern)
                                    <dl class="row mb-0 g-3">
                                        <dt class="col-md-3 text-secondary">Availability pattern</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->availabilityPatternLabel() }}</dd>
                                        <dt class="col-md-3 text-secondary">Max weekly hours</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->max_weekly_hours }}</dd>
                                        <dt class="col-md-3 text-secondary">Shift preference</dt>
                                        <dd class="col-md-3 mb-0">{{ $carer->carerProfile->shiftPreferenceLabel() }}</dd>
                                    </dl>
                                @else
                                    <div class="alert alert-warning d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-md-between mb-0">
                                        <span>Availability and scheduling assessment has not been completed.</span>
                                        <a class="btn btn-sm btn-warning fw-semibold" href="{{ route('carers.assessments.edit', $carer) }}">Continue assessment</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">System Access & Security</p>
                                        <h2 class="h4 fw-bold mb-0">Login governance</h2>
                                    </div>
                                </div>

                                <dl class="row mb-0 g-3">
                                    <dt class="col-md-3 text-secondary">Role</dt>
                                    <dd class="col-md-3 mb-0">Carer</dd>
                                    <dt class="col-md-3 text-secondary">Account status</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->carerProfile?->accountStatusLabel() ?: 'Pending' }}</dd>
                                    <dt class="col-md-3 text-secondary">Login</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->is_active ? 'Enabled' : 'Disabled' }}</dd>
                                    <dt class="col-md-3 text-secondary">MFA</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->carerProfile?->mfa_enabled ? 'Enabled' : 'Not enabled' }}</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                                    <div>
                                        <p class="section-kicker mb-2">GDPR & Consent</p>
                                        <h2 class="h4 fw-bold mb-0">Data governance</h2>
                                    </div>
                                </div>

                                <dl class="row mb-0 g-3">
                                    <dt class="col-md-3 text-secondary">Data processing consent</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->carerProfile?->data_processing_consent ? 'Accepted' : 'Not accepted' }}</dd>
                                    <dt class="col-md-3 text-secondary">Consent timestamp</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->carerProfile?->data_processing_consented_at?->format('d/m/Y H:i') ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-3 text-secondary">Privacy policy</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->carerProfile?->privacy_policy_accepted ? 'Accepted' : 'Not accepted' }}</dd>
                                    <dt class="col-md-3 text-secondary">Privacy version</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->carerProfile?->privacy_policy_version ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-3 text-secondary">Retention category</dt>
                                    <dd class="col-md-3 mb-0">{{ $carer->carerProfile?->dataRetentionCategoryLabel() ?: 'Not recorded' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="carer-visits-pane" role="tabpanel" aria-labelledby="carer-visits-tab" tabindex="0">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Visit Allocation</p>
                            <h2 class="h4 fw-bold mb-0">Assigned visits</h2>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <span class="text-secondary fw-semibold">{{ $carer->assignedVisits->count() }} total visits</span>
                            <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('visits.index') }}"><i class="fa-solid fa-calendar-check"></i>Manage visits</a>
                        </div>
                    </div>

                    @if ($carer->assignedVisits->isEmpty())
                        <div class="alert alert-info mb-0">No visits are assigned to this carer yet.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Visit</th>
                                        <th>Client</th>
                                        <th>Home</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>EVV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($carer->assignedVisits as $visit)
                                        <tr>
                                            <td>
                                                <p class="fw-bold mb-0">{{ $visit->title }}</p>
                                                <p class="text-secondary mb-0">{{ $visit->carePlan?->title ?: 'No care plan linked' }}</p>
                                            </td>
                                            <td>
                                                <a href="{{ route('clients.show', $visit->client) }}">{{ $visit->client->fullName() }}</a>
                                            </td>
                                            <td>{{ $visit->home->name }}</td>
                                            <td>{{ $visit->durationLabel() }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $visit->status === 'completed' ? 'success' : ($visit->status === 'in_progress' ? 'warning' : ($visit->status === 'cancelled' ? 'secondary' : 'info')) }}">
                                                    {{ str($visit->status)->replace('_', ' ')->headline() }}
                                                </span>
                                            </td>
                                            <td>
                                                <p class="mb-0">{{ $visit->check_in_at?->format('d/m/Y H:i') ?: 'No check-in' }}</p>
                                                <p class="text-secondary mb-0">{{ $visit->check_out_at?->format('d/m/Y H:i') ?: 'No check-out' }}</p>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="carer-medication-pane" role="tabpanel" aria-labelledby="carer-medication-tab" tabindex="0">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Medication</p>
                            <h2 class="h4 fw-bold mb-0">Medication administration for assigned visits</h2>
                        </div>
                        <span class="text-secondary fw-semibold">{{ $marAdministrations->count() }} administrations</span>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="border rounded p-4 h-100">
                                <h3 class="h5 fw-bold mb-3">Record medication administration</h3>
                                @if ($marVisits->isEmpty())
                                    <div class="alert alert-info mb-0">No assigned visits have medication support in the linked care plan.</div>
                                @else
                                    <form class="mb-4" method="POST" action="{{ route('carers.medication-administrations.store', $carer) }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label" for="medication-visit">Visit</label>
                                            <select class="form-select @error('visit_id') is-invalid @enderror" id="medication-visit" name="visit_id" required>
                                                <option value="">Choose medication visit</option>
                                                @foreach ($marVisits as $marVisitOption)
                                                    <option value="{{ $marVisitOption->id }}" @selected(old('visit_id') == $marVisitOption->id)>
                                                        {{ $marVisitOption->client->fullName() }} - {{ $marVisitOption->durationLabel() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('visit_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="medication-name">Medication</label>
                                                <input class="form-control @error('medication_name') is-invalid @enderror" id="medication-name" name="medication_name" value="{{ old('medication_name', 'Medication support') }}" required maxlength="255">
                                                @error('medication_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="medication-outcome">Outcome</label>
                                                <select class="form-select @error('outcome') is-invalid @enderror" id="medication-outcome" name="outcome" required>
                                                    <option value="administered" @selected(old('outcome') === 'administered')>Administered</option>
                                                    <option value="refused" @selected(old('outcome') === 'refused')>Refused</option>
                                                    <option value="missed" @selected(old('outcome') === 'missed')>Missed</option>
                                                </select>
                                                @error('outcome')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="medication-dose">Dose</label>
                                                <input class="form-control @error('dose') is-invalid @enderror" id="medication-dose" name="dose" value="{{ old('dose') }}" maxlength="120">
                                                @error('dose')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="medication-route">Route</label>
                                                <input class="form-control @error('route') is-invalid @enderror" id="medication-route" name="route" value="{{ old('route') }}" maxlength="120" placeholder="Oral, topical, other">
                                                @error('route')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="medication-notes">Administration note</label>
                                                <textarea class="form-control @error('notes') is-invalid @enderror" id="medication-notes" name="notes" rows="3" placeholder="Dose given, reason refused, or why missed">{{ old('notes') }}</textarea>
                                                @error('notes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-pills me-1"></i>Save administration</button>
                                        </div>
                                    </form>
                                    <hr>
                                    <h3 class="h5 fw-bold mb-3">Medication instructions</h3>
                                    <div class="vstack gap-3">
                                        @foreach ($marVisits as $marVisit)
                                            @php($latestMar = $marVisit->medicationAdministrations->first())
                                            <div class="border rounded p-3">
                                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between">
                                                    <div>
                                                        <p class="fw-bold mb-1">{{ $marVisit->client->fullName() }}</p>
                                                        <p class="text-secondary mb-0">{{ $marVisit->durationLabel() }}</p>
                                                    </div>
                                                    @if ($latestMar)
                                                        <span class="badge text-bg-{{ $latestMar->outcome === 'administered' ? 'success' : ($latestMar->outcome === 'refused' ? 'warning' : 'danger') }} align-self-start">{{ str($latestMar->outcome)->headline() }}</span>
                                                    @else
                                                        <span class="badge text-bg-light border align-self-start">Not administered yet</span>
                                                    @endif
                                                </div>
                                                <dl class="row mb-0 mt-3 g-2">
                                                    <dt class="col-sm-4 text-secondary">Support level</dt>
                                                    <dd class="col-sm-8 mb-0">{{ $marVisit->carePlan?->medication_support_level ?: 'Medication support' }}</dd>
                                                    <dt class="col-sm-4 text-secondary">Instructions</dt>
                                                    <dd class="col-sm-8 mb-0">{{ $marVisit->carePlan?->medication_support ?: 'Follow the current care plan medication instructions.' }}</dd>
                                                    @if ($latestMar)
                                                        <dt class="col-sm-4 text-secondary">Latest note</dt>
                                                        <dd class="col-sm-8 mb-0">{{ $latestMar->notes ?: 'No note recorded' }}</dd>
                                                    @endif
                                                </dl>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border rounded p-4 h-100">
                                <h3 class="h5 fw-bold mb-3">Medication administrations</h3>
                                @if ($marAdministrations->isEmpty())
                                    <div class="alert alert-light border mb-0">No medication administrations have been recorded by this carer yet.</div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>When</th>
                                                    <th>Client</th>
                                                    <th>Outcome</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($marAdministrations as $marAdministration)
                                                    <tr>
                                                        <td>{{ $marAdministration->administered_at->format('d/m/Y H:i') }}</td>
                                                        <td>{{ $marAdministration->client?->fullName() ?: 'Client unavailable' }}</td>
                                                        <td><span class="badge text-bg-{{ $marAdministration->outcome === 'administered' ? 'success' : ($marAdministration->outcome === 'refused' ? 'warning' : 'danger') }}">{{ str($marAdministration->outcome)->headline() }}</span></td>
                                                        <td>{{ $marAdministration->notes ?: 'No note recorded' }}</td>
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

                <div class="tab-pane fade" id="carer-family-messages-pane" role="tabpanel" aria-labelledby="carer-family-messages-tab" tabindex="0">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Family Portal</p>
                            <h2 class="h4 fw-bold mb-0">Messages to family</h2>
                        </div>
                        <span class="text-secondary fw-semibold">{{ $familyMessages->count() }} sent messages</span>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="border rounded p-4 h-100">
                                <h3 class="h5 fw-bold mb-3">Send a message</h3>
                                @if ($messageClients->isEmpty())
                                    <div class="alert alert-info mb-0">Assign this carer to a visit before sending family messages.</div>
                                @else
                                    <form method="POST" action="{{ route('carers.family-messages.store', $carer) }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label" for="family-message-client">Client</label>
                                            <select class="form-select @error('client_id') is-invalid @enderror" id="family-message-client" name="client_id" required>
                                                <option value="">Choose client</option>
                                                @foreach ($messageClients as $messageClient)
                                                    <option value="{{ $messageClient->id }}" @selected(old('client_id') == $messageClient->id)>{{ $messageClient->fullName() }}</option>
                                                @endforeach
                                            </select>
                                            @error('client_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="family-message-subject">Subject</label>
                                            <input class="form-control @error('subject') is-invalid @enderror" id="family-message-subject" name="subject" value="{{ old('subject') }}" maxlength="255" required>
                                            @error('subject')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="family-message-body">Message</label>
                                            <textarea class="form-control @error('message') is-invalid @enderror" id="family-message-body" name="message" rows="5" required>{{ old('message') }}</textarea>
                                            @error('message')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <p class="text-secondary small mb-3">Family members see this only when their access includes Messages from staff.</p>
                                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-1"></i>Send to family</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="border rounded p-4 h-100">
                                <h3 class="h5 fw-bold mb-3">Recently sent</h3>
                                @if ($familyMessages->isEmpty())
                                    <div class="alert alert-light border mb-0">No family messages have been sent by this carer yet.</div>
                                @else
                                    <div class="vstack gap-3">
                                        @foreach ($familyMessages as $familyMessage)
                                            <div class="border rounded p-3">
                                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between">
                                                    <div>
                                                        <p class="fw-bold mb-1">{{ $familyMessage->subject }}</p>
                                                        <p class="text-secondary mb-0">{{ $familyMessage->client?->fullName() ?: 'Client unavailable' }}</p>
                                                    </div>
                                                    <span class="text-secondary small">{{ $familyMessage->sent_at?->format('d/m/Y H:i') ?: 'Not sent yet' }}</span>
                                                </div>
                                                <p class="mb-0 mt-2">{{ $familyMessage->message }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCarerModal" tabindex="-1" aria-labelledby="editCarerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('carers.update', $carer) }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h2 class="modal-title h5" id="editCarerModalLabel">Edit {{ $carer->name }}</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        @include('components.form-errors')
                    @endif
                    <input type="hidden" name="editing_carer_id" value="{{ $carer->id }}">
                    @include('carers.partials.form', ['carer' => $carer, 'homes' => $homes, 'passwordRequired' => false, 'submitLabel' => 'Update carer'])
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('editCarerModal');

                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endif
@endsection
