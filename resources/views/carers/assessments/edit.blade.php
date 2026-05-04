@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Carers', 'url' => route('carers.index')],
        ['label' => $carer->name],
        ['label' => 'Onboarding Assessment'],
    ]" />
@endsection

@section('content')
    @php
        $status = $profile->status ?: 'onboarding';
        $statusBadge = [
            'onboarding' => 'text-bg-info',
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'declined' => 'text-bg-danger',
        ][$status] ?? 'text-bg-secondary';
        $dobValue = old('date_of_birth', $profile->date_of_birth?->format('Y-m-d'));
    @endphp

    <x-page-header title="{{ $carer->name }} Onboarding" description="Complete carer assessment evidence, submit for verification, then approve or decline with clear review notes.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge {{ $statusBadge }} d-inline-flex align-items-center px-3">{{ ucfirst($status) }}</span>
                <a class="btn btn-outline-secondary" href="{{ route('carers.index') }}"><i class="fa-solid fa-arrow-left me-1"></i>Carers</a>
            </div>
        </x-slot:action>
    </x-page-header>

    @if ($profile->review_notes)
        <div class="alert alert-warning">
            <strong>Review notes:</strong> {{ $profile->review_notes }}
        </div>
    @endif

    <div class="assessment-layout">
        <aside class="assessment-steps">
            <p class="section-kicker mb-2">Onboarding steps</p>
            <button type="button" data-step-target="0">1. Identity & Legal</button>
            <button type="button" data-step-target="1">2. Contact & Emergency</button>
            <button type="button" data-step-target="2">3. Employment</button>
            <button type="button" data-step-target="3">4. Compliance</button>
            <button type="button" data-step-target="4">5. Training</button>
            <button type="button" data-step-target="5">6. Health</button>
            <button type="button" data-step-target="6">7. Skills</button>
            <button type="button" data-step-target="7">8. Availability</button>
            <button type="button" data-step-target="8">9. Access</button>
            <button type="button" data-step-target="9">10. GDPR</button>
        </aside>

        <form class="form-workspace" method="POST" action="{{ route('carers.assessments.update', $carer) }}" enctype="multipart/form-data" data-assessment-stepper>
            @csrf
            @method('PUT')
            <x-form-errors />

            <div class="assessment-progress-shell">
                <div class="assessment-progress-meta">
                    <span>Assessment progress</span>
                    <span>Step <span data-step-current>1</span> of <span data-step-total>10</span></span>
                </div>
                <div class="progress" role="progressbar" aria-label="Assessment progress">
                    <div class="progress-bar" data-step-progress style="width: 10%"></div>
                </div>
            </div>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-identity">
                <div class="form-section-header">
                    <span class="section-kicker">Step 1</span>
                    <h2 class="form-section-title mt-2">Identity & Legal</h2>
                    <p class="form-section-description">Capture mandatory identity and right-to-work details before onboarding can be submitted.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="legal_name">Full legal name</label>
                        <input class="form-control focus-ring-brand @error('legal_name') is-invalid @enderror" id="legal_name" name="legal_name" value="{{ old('legal_name', $profile->legal_name ?: $carer->name) }}" required>
                        @error('legal_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="form-text">Minimum two names.</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="date_of_birth">Date of birth</label>
                        <input class="form-control focus-ring-brand @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" type="date" value="{{ $dobValue }}" required>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="national_insurance_number">National Insurance number</label>
                        <input class="form-control focus-ring-brand text-uppercase @error('national_insurance_number') is-invalid @enderror" id="national_insurance_number" name="national_insurance_number" value="{{ old('national_insurance_number', $profile->national_insurance_number) }}" placeholder="AA123456A" required>
                        @error('national_insurance_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="photo_id_type">Photo ID type</label>
                        <select class="form-select focus-ring-brand @error('photo_id_type') is-invalid @enderror" id="photo_id_type" name="photo_id_type" required>
                            <option value="">Select photo ID</option>
                            @foreach (\App\Models\CarerProfile::PHOTO_ID_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('photo_id_type', $profile->photo_id_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('photo_id_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_document_number">ID document number</label>
                        <input class="form-control focus-ring-brand @error('id_document_number') is-invalid @enderror" id="id_document_number" name="id_document_number" value="{{ old('id_document_number', $profile->id_document_number) }}" required>
                        @error('id_document_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_document">ID document upload</label>
                        <input class="form-control focus-ring-brand @error('id_document') is-invalid @enderror" id="id_document" name="id_document" type="file" accept=".pdf,.jpg,.jpeg,.png">
                        @error('id_document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if ($profile->id_document_path)
                            <p class="form-text">Current file: {{ basename($profile->id_document_path) }}</p>
                        @else
                            <p class="form-text">PDF, JPG, JPEG, or PNG. Max 10MB.</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="right_to_work_status">Right to work status</label>
                        <select class="form-select focus-ring-brand @error('right_to_work_status') is-invalid @enderror" id="right_to_work_status" name="right_to_work_status" required>
                            <option value="">Select status</option>
                            @foreach (\App\Models\CarerProfile::RIGHT_TO_WORK_STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('right_to_work_status', $profile->right_to_work_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('right_to_work_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="visa_status">Visa status</label>
                        <select class="form-select focus-ring-brand @error('visa_status') is-invalid @enderror" id="visa_status" name="visa_status">
                            <option value="">Select visa status</option>
                            @foreach (\App\Models\CarerProfile::VISA_STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('visa_status', $profile->visa_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('visa_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="form-text">Required when right to work is not UK Citizen.</p>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-contact">
                <div class="form-section-header">
                    <span class="section-kicker">Step 2</span>
                    <h2 class="form-section-title mt-2">Contact & Emergency</h2>
                    <p class="form-section-description">Record the carer's address, contact details, and emergency escalation contact.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="address_line_1">Address line 1</label>
                        <input class="form-control focus-ring-brand @error('address_line_1') is-invalid @enderror" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $profile->address_line_1) }}" required>
                        @error('address_line_1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="address_line_2">Address line 2</label>
                        <input class="form-control focus-ring-brand @error('address_line_2') is-invalid @enderror" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $profile->address_line_2) }}">
                        @error('address_line_2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="city">City</label>
                        <input class="form-control focus-ring-brand @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $profile->city) }}" required>
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="postcode">Postcode</label>
                        <input class="form-control focus-ring-brand text-uppercase @error('postcode') is-invalid @enderror" id="postcode" name="postcode" value="{{ old('postcode', $profile->postcode) }}" placeholder="SW1A 1AA" required>
                        @error('postcode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_phone">Phone</label>
                        <input class="form-control focus-ring-brand @error('contact_phone') is-invalid @enderror" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $profile->contact_phone) }}" placeholder="07123 456 789" required>
                        @error('contact_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_email">Email</label>
                        <input class="form-control focus-ring-brand @error('contact_email') is-invalid @enderror" id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $profile->contact_email ?: $carer->email) }}" required>
                        @error('contact_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="form-text">Must not be used by another user.</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="emergency_contact_name">Emergency contact name</label>
                        <input class="form-control focus-ring-brand @error('emergency_contact_name') is-invalid @enderror" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $profile->emergency_contact_name) }}" required>
                        @error('emergency_contact_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="emergency_contact_phone">Emergency contact phone</label>
                        <input class="form-control focus-ring-brand @error('emergency_contact_phone') is-invalid @enderror" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $profile->emergency_contact_phone) }}" placeholder="07123 456 789" required>
                        @error('emergency_contact_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-employment">
                <div class="form-section-header">
                    <span class="section-kicker">Step 3</span>
                    <h2 class="form-section-title mt-2">Employment & Role</h2>
                    <p class="form-section-description">Assign the carer's role, employment type, start date, and responsible home.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="job_title">Job title</label>
                        <select class="form-select focus-ring-brand @error('job_title') is-invalid @enderror" id="job_title" name="job_title" required>
                            <option value="">Select job title</option>
                            @foreach (\App\Models\CarerProfile::JOB_TITLES as $value => $label)
                                <option value="{{ $value }}" @selected(old('job_title', $profile->job_title) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('job_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="employment_type">Employment type</label>
                        <select class="form-select focus-ring-brand @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type" required>
                            <option value="">Select employment type</option>
                            @foreach (\App\Models\CarerProfile::EMPLOYMENT_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_type', $profile->employment_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="start_date">Start date</label>
                        <input class="form-control focus-ring-brand @error('start_date') is-invalid @enderror" id="start_date" name="start_date" type="date" value="{{ old('start_date', $profile->start_date?->format('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="assigned_home_id">Assigned home/location</label>
                        <select class="form-select focus-ring-brand @error('assigned_home_id') is-invalid @enderror" id="assigned_home_id" name="assigned_home_id" required>
                            <option value="">Select home</option>
                            @foreach ($homes as $home)
                                <option value="{{ $home->id }}" @selected((int) old('assigned_home_id', $profile->assigned_home_id ?: $carer->home_id) === (int) $home->id)>{{ $home->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_home_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            Platform-wide carer is intentionally unavailable. Every carer must be assigned to a registered active home.
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-safeguarding">
                <div class="form-section-header">
                    <span class="section-kicker">Step 4</span>
                    <h2 class="form-section-title mt-2">Safeguarding & Compliance</h2>
                    <p class="form-section-description">Capture DBS and safeguarding evidence required before onboarding can be submitted for review.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="dbs_check_status">DBS check status</label>
                        <select class="form-select focus-ring-brand @error('dbs_check_status') is-invalid @enderror" id="dbs_check_status" name="dbs_check_status" required>
                            <option value="">Select DBS status</option>
                            @foreach (\App\Models\CarerProfile::DBS_CHECK_STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('dbs_check_status', $profile->dbs_check_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('dbs_check_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dbs_certificate_number">DBS certificate number</label>
                        <input class="form-control focus-ring-brand @error('dbs_certificate_number') is-invalid @enderror" id="dbs_certificate_number" name="dbs_certificate_number" value="{{ old('dbs_certificate_number', $profile->dbs_certificate_number) }}">
                        @error('dbs_certificate_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="form-text">Required when DBS status is Verified.</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dbs_expiry_date">DBS expiry date</label>
                        <input class="form-control focus-ring-brand @error('dbs_expiry_date') is-invalid @enderror" id="dbs_expiry_date" name="dbs_expiry_date" type="date" value="{{ old('dbs_expiry_date', $profile->dbs_expiry_date?->format('Y-m-d')) }}">
                        @error('dbs_expiry_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="form-text">Required when DBS status is Verified.</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="safeguarding_training_completed">Safeguarding training completed</label>
                        <select class="form-select focus-ring-brand @error('safeguarding_training_completed') is-invalid @enderror" id="safeguarding_training_completed" name="safeguarding_training_completed" required>
                            <option value="">Select answer</option>
                            @foreach (\App\Models\CarerProfile::YES_NO_OPTIONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('safeguarding_training_completed', $profile->safeguarding_training_completed) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('safeguarding_training_completed')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="last_safeguarding_training_date">Last safeguarding training date</label>
                        <input class="form-control focus-ring-brand @error('last_safeguarding_training_date') is-invalid @enderror" id="last_safeguarding_training_date" name="last_safeguarding_training_date" type="date" value="{{ old('last_safeguarding_training_date', $profile->last_safeguarding_training_date?->format('Y-m-d')) }}">
                        @error('last_safeguarding_training_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="form-text">Required when safeguarding training is completed.</p>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-training">
                <div class="form-section-header">
                    <span class="section-kicker">Step 5</span>
                    <h2 class="form-section-title mt-2">Training & Qualifications</h2>
                    <p class="form-section-description">Complete the mandatory training checklist with status, expiry, and certificate evidence.</p>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
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
                                    $trainingRecord = $trainingRecords[$trainingKey];
                                    $trainingStatus = old("trainings.{$trainingKey}.status", $trainingRecord->status ?: 'not_started');
                                    $trainingExpiry = old("trainings.{$trainingKey}.expiry_date", $trainingRecord->expiry_date?->format('Y-m-d'));
                                    $trainingStatusError = "trainings.{$trainingKey}.status";
                                    $trainingExpiryError = "trainings.{$trainingKey}.expiry_date";
                                    $trainingCertificateError = "trainings.{$trainingKey}.certificate";
                                @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $trainingName }}
                                        <input type="hidden" name="trainings[{{ $trainingKey }}][training_key]" value="{{ $trainingKey }}">
                                    </td>
                                    <td style="min-width: 190px;">
                                        <select class="form-select focus-ring-brand @error($trainingStatusError) is-invalid @enderror" name="trainings[{{ $trainingKey }}][status]" required>
                                            @foreach (\App\Models\CarerTrainingRecord::STATUSES as $value => $label)
                                                <option value="{{ $value }}" @selected($trainingStatus === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error($trainingStatusError)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td style="min-width: 170px;">
                                        <input class="form-control focus-ring-brand @error($trainingExpiryError) is-invalid @enderror" name="trainings[{{ $trainingKey }}][expiry_date]" type="date" value="{{ $trainingExpiry }}">
                                        @error($trainingExpiryError)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td style="min-width: 240px;">
                                        <input class="form-control focus-ring-brand @error($trainingCertificateError) is-invalid @enderror" name="trainings[{{ $trainingKey }}][certificate]" type="file" accept=".pdf,.jpg,.jpeg,.png">
                                        @error($trainingCertificateError)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if ($trainingRecord->certificate_path)
                                            <p class="form-text mb-0">Current file: {{ basename($trainingRecord->certificate_path) }}</p>
                                        @else
                                            <p class="form-text mb-0">PDF, JPG, JPEG, or PNG. Max 10MB.</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-health">
                <div class="form-section-header">
                    <span class="section-kicker">Step 6</span>
                    <h2 class="form-section-title mt-2">Health & Fitness to Work</h2>
                    <p class="form-section-description">Confirm occupational health status, immunisation position, and the fit-to-work declaration.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="occupational_health_clearance">Occupational health clearance</label>
                        <select class="form-select focus-ring-brand @error('occupational_health_clearance') is-invalid @enderror" id="occupational_health_clearance" name="occupational_health_clearance" required>
                            <option value="">Select clearance</option>
                            @foreach (\App\Models\CarerProfile::OCCUPATIONAL_HEALTH_CLEARANCES as $value => $label)
                                <option value="{{ $value }}" @selected(old('occupational_health_clearance', $profile->occupational_health_clearance) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('occupational_health_clearance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="immunisation_status">Immunisation status</label>
                        <select class="form-select focus-ring-brand @error('immunisation_status') is-invalid @enderror" id="immunisation_status" name="immunisation_status">
                            <option value="">Select status</option>
                            @foreach (\App\Models\CarerProfile::IMMUNISATION_STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('immunisation_status', $profile->immunisation_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('immunisation_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="hidden" name="fit_to_work_declaration" value="0">
                            <input class="form-check-input @error('fit_to_work_declaration') is-invalid @enderror" name="fit_to_work_declaration" type="checkbox" value="1" @checked((bool) old('fit_to_work_declaration', $profile->fit_to_work_declaration)) required>
                            <span class="form-check-label">Fit-to-work declaration confirmed</span>
                        </label>
                        @error('fit_to_work_declaration')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-skills">
                <div class="form-section-header">
                    <span class="section-kicker">Step 7</span>
                    <h2 class="form-section-title mt-2">Skills & Competencies</h2>
                    <p class="form-section-description">Select specialist care competencies and languages spoken by the carer.</p>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h3 class="h6 fw-bold mb-3">Skills</h3>
                        <div class="row g-2">
                            @foreach (\App\Models\CarerProfile::SKILLS as $value => $label)
                                <div class="col-12">
                                    <label class="form-check border rounded px-3 py-2 mb-0">
                                        <input class="form-check-input @error('skills') is-invalid @enderror @error('skills.*') is-invalid @enderror" name="skills[]" type="checkbox" value="{{ $value }}" @checked(in_array($value, old('skills', $profile->skills ?: []), true))>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('skills')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('skills.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <h3 class="h6 fw-bold mb-3">Languages</h3>
                        <div class="row g-2">
                            @foreach (\App\Models\CarerProfile::LANGUAGES as $value => $label)
                                <div class="col-12">
                                    <label class="form-check border rounded px-3 py-2 mb-0">
                                        <input class="form-check-input @error('languages') is-invalid @enderror @error('languages.*') is-invalid @enderror" name="languages[]" type="checkbox" value="{{ $value }}" @checked(in_array($value, old('languages', $profile->languages ?: []), true))>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('languages')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('languages.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-availability">
                <div class="form-section-header">
                    <span class="section-kicker">Step 8</span>
                    <h2 class="form-section-title mt-2">Availability & Scheduling</h2>
                    <p class="form-section-description">Set the carer's availability pattern, weekly capacity, and shift preference.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="availability_pattern">Availability pattern</label>
                        <select class="form-select focus-ring-brand @error('availability_pattern') is-invalid @enderror" id="availability_pattern" name="availability_pattern" required>
                            <option value="">Select pattern</option>
                            @foreach (\App\Models\CarerProfile::AVAILABILITY_PATTERNS as $value => $label)
                                <option value="{{ $value }}" @selected(old('availability_pattern', $profile->availability_pattern) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('availability_pattern')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="max_weekly_hours">Max weekly hours</label>
                        <input class="form-control focus-ring-brand @error('max_weekly_hours') is-invalid @enderror" id="max_weekly_hours" name="max_weekly_hours" type="number" min="1" max="60" value="{{ old('max_weekly_hours', $profile->max_weekly_hours) }}" required>
                        @error('max_weekly_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shift_preference">Shift preference</label>
                        <select class="form-select focus-ring-brand @error('shift_preference') is-invalid @enderror" id="shift_preference" name="shift_preference" required>
                            <option value="">Select shift</option>
                            @foreach (\App\Models\CarerProfile::SHIFT_PREFERENCES as $value => $label)
                                <option value="{{ $value }}" @selected(old('shift_preference', $profile->shift_preference) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('shift_preference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-access">
                <div class="form-section-header">
                    <span class="section-kicker">Step 9</span>
                    <h2 class="form-section-title mt-2">System Access & Security</h2>
                    <p class="form-section-description">Control account activation and security flags after compliance evidence is complete.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <input class="form-control focus-ring-brand" value="Carer" disabled>
                        <p class="form-text">Carers always keep the Carer system role in this workflow.</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="account_status">Account status</label>
                        <select class="form-select focus-ring-brand @error('account_status') is-invalid @enderror" id="account_status" name="account_status" required>
                            @foreach (\App\Models\CarerProfile::ACCOUNT_STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('account_status', $profile->account_status ?: 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('account_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Login</label>
                        <div class="form-control bg-light">{{ $carer->is_active ? 'Currently enabled' : 'Currently disabled' }}</div>
                        <p class="form-text">Saving Active enables login; Pending or Suspended disables it.</p>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="hidden" name="mfa_enabled" value="0">
                            <input class="form-check-input @error('mfa_enabled') is-invalid @enderror" name="mfa_enabled" type="checkbox" value="1" @checked((bool) old('mfa_enabled', $profile->mfa_enabled))>
                            <span class="form-check-label">MFA enabled</span>
                        </label>
                        @error('mfa_enabled')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <p class="form-text">This stores the MFA flag only. Full MFA enforcement can be added separately.</p>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            Account cannot be Active unless home, DBS, safeguarding, mandatory training, occupational health, and fit-to-work declaration are complete.
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="carer-assessment-gdpr">
                <div class="form-section-header">
                    <span class="section-kicker">Step 10</span>
                    <h2 class="form-section-title mt-2">GDPR & Consent</h2>
                    <p class="form-section-description">Record mandatory data processing consent, privacy policy acceptance, and retention category.</p>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-check">
                            <input type="hidden" name="data_processing_consent" value="0">
                            <input class="form-check-input @error('data_processing_consent') is-invalid @enderror" name="data_processing_consent" type="checkbox" value="1" @checked((bool) old('data_processing_consent', $profile->data_processing_consent)) required>
                            <span class="form-check-label">Consent to data processing</span>
                        </label>
                        @error('data_processing_consent')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @if ($profile->data_processing_consented_at)
                            <p class="form-text">Stored timestamp: {{ $profile->data_processing_consented_at->format('Y-m-d H:i') }}</p>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="hidden" name="privacy_policy_accepted" value="0">
                            <input class="form-check-input @error('privacy_policy_accepted') is-invalid @enderror" name="privacy_policy_accepted" type="checkbox" value="1" @checked((bool) old('privacy_policy_accepted', $profile->privacy_policy_accepted)) required>
                            <span class="form-check-label">Privacy policy accepted</span>
                        </label>
                        @error('privacy_policy_accepted')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <p class="form-text">Version: {{ $profile->privacy_policy_version ?: config('privacy.policy_version', 'v1') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="data_retention_category">Data retention category</label>
                        <select class="form-select focus-ring-brand @error('data_retention_category') is-invalid @enderror" id="data_retention_category" name="data_retention_category" required>
                            <option value="">Select category</option>
                            @foreach (\App\Models\CarerProfile::DATA_RETENTION_CATEGORIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('data_retention_category', $profile->data_retention_category ?: 'active_staff') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('data_retention_category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <div class="form-actions justify-content-between">
                <div class="text-secondary small">
                    Saving changes returns this onboarding record to the onboarding state until it is submitted again.
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-secondary fw-semibold" type="button" data-step-previous><i class="fa-solid fa-arrow-left me-1"></i>Previous</button>
                    <button class="btn btn-action btn-action-primary fw-semibold" type="button" data-step-next>Next<i class="fa-solid fa-arrow-right ms-1"></i></button>
                    <button class="btn btn-primary fw-semibold" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Save assessment</button>
                    <a class="btn btn-outline-secondary fw-semibold" href="{{ route('carers.index') }}">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
            <div>
                <h2 class="h5 fw-bold mb-1">Verification And Adjudication</h2>
                <p class="text-secondary mb-0">Submit once the carer assessment evidence is ready. Pending records can be approved or declined with review notes.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('carers.assessments.submit', $carer) }}" data-confirm data-confirm-title="Submit onboarding?" data-confirm-text="This will move the carer into pending review." data-confirm-button="Yes, submit">
                    @csrf
                    <button class="btn btn-action btn-action-primary" type="submit"><i class="fa-solid fa-paper-plane"></i>Submit for review</button>
                </form>
                @if ($status === 'pending')
                    <form method="POST" action="{{ route('carers.assessments.approve', $carer) }}" data-confirm data-confirm-title="Approve onboarding?" data-confirm-text="This carer onboarding record will be approved." data-confirm-button="Yes, approve">
                        @csrf
                        <button class="btn btn-action btn-action-primary" type="submit"><i class="fa-solid fa-check"></i>Approve</button>
                    </form>
                @endif
            </div>
        </div>
        @if ($status === 'pending')
            <div class="card-footer bg-white">
                <form method="POST" action="{{ route('carers.assessments.decline', $carer) }}" data-confirm data-confirm-title="Decline onboarding?" data-confirm-text="The review notes will be sent back for correction and resubmission." data-confirm-button="Yes, decline">
                    @csrf
                    <label class="form-label" for="review_notes">Decline notes</label>
                    <textarea class="form-control focus-ring-brand @error('review_notes') is-invalid @enderror" id="review_notes" name="review_notes" rows="3" required placeholder="Explain what must be reviewed before resubmission.">{{ old('review_notes') }}</textarea>
                    @error('review_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="mt-3 d-flex justify-content-end">
                        <button class="btn btn-action btn-action-danger" type="submit"><i class="fa-solid fa-ban"></i>Decline and request review</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
