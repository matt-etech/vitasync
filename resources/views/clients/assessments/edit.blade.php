@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Clients', 'url' => route('clients.index')],
        ['label' => $client->fullName()],
        ['label' => 'Onboarding Assessment'],
    ]" />
@endsection

@section('content')
    @php
        $status = $assessment->status ?: $client->onboarding_status ?: 'onboarding';
        $statusBadge = [
            'onboarding' => 'text-bg-info',
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'declined' => 'text-bg-danger',
        ][$status] ?? 'text-bg-secondary';

        $riskLevels = ['None', 'Low', 'Medium', 'High', 'Critical', 'Not assessed'];
        $riskLevelValues = ['none' => 'None', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical', 'not_assessed' => 'Not assessed'];
        $supportLevels = ['Independent', 'Needs prompting', 'Needs supervision', 'Needs partial assistance', 'Needs full assistance', 'Unable', 'Not assessed'];
        $needsOptions = [
            'physical_needs' => ['Mobility', 'Personal care', 'Nutrition/hydration', 'Continence', 'Medication support', 'Pain management', 'Sleep support', 'Equipment/adaptations', 'None identified'],
            'psychological_needs' => ['Anxiety', 'Low mood', 'Confusion', 'Memory concerns', 'Distress', 'Behavioural support', 'Emotional support', 'None identified'],
            'social_needs' => ['Family support', 'Community access', 'Social isolation', 'Safeguarding concerns', 'Financial concerns', 'Advocacy needed', 'None identified'],
            'spiritual_needs' => ['No specific needs', 'Prayer support', 'Faith/community contact', 'Dietary faith needs', 'End-of-life spiritual support', 'Prefer not to say'],
            'environmental_needs' => ['Home safety', 'Fire safety', 'Accessibility', 'Cleanliness', 'Heating/utilities', 'Hoarding/clutter', 'Equipment required', 'None identified'],
            'priority_needs' => ['Urgent care need', 'Medication risk', 'Falls risk', 'Safeguarding risk', 'Nutrition risk', 'Mental capacity concern', 'Hospital discharge support', 'None identified'],
        ];
        $continenceOptions = ['Continent', 'Occasional accidents', 'Urinary incontinence', 'Faecal incontinence', 'Double incontinence', 'Catheter/stoma care', 'Not assessed'];
        $independenceOptions = ['Fully independent', 'Mostly independent', 'Needs regular support', 'Dependent on carers', 'Requires 24-hour support', 'Not assessed'];
        $diagnosisOptions = ['Diabetes', 'Hypertension', 'Stroke/TIA', 'Dementia', 'Parkinson\'s disease', 'COPD/asthma', 'Heart condition', 'Arthritis', 'Epilepsy', 'Cancer', 'Mental health condition', 'Learning disability', 'Physical disability', 'None recorded', 'Other'];
        $allergyOptions = ['None known', 'Medication allergy', 'Food allergy', 'Latex allergy', 'Environmental allergy', 'Unknown'];
        $medicationSupportOptions = ['Self-administers', 'Prompting only', 'Assistance required', 'Administration by carer', 'MAR chart required', 'District nurse support', 'Pharmacy blister pack', 'Not assessed'];
        $gpDetailOptions = ['GP name', 'Practice name', 'NHS number', 'Telephone', 'Address'];
        $decisionTypeOptions = ['Care package', 'Medication', 'Finances', 'Accommodation', 'Personal care', 'Medical treatment', 'Contact with others', 'Safeguarding', 'Other'];
        $capacityOutcomeOptions = ['Has capacity', 'Lacks capacity', 'Fluctuating capacity', 'Unable to assess', 'Needs formal assessment'];
        $dolsLpsOptions = ['Not applicable', 'DoLS authorised', 'DoLS pending', 'DoLS expired', 'LPS referral needed', 'Unknown'];
        $bestInterestOptions = ['Not required', 'Required', 'Completed', 'Pending', 'Family consulted', 'Advocate/IMCA required'];
        $controlMeasureOptions = ['Care plan in place', 'Falls prevention', 'Pressure care plan', 'Moving & handling plan', 'Equipment provided', 'Safeguarding referral', 'Medication review', 'GP referral', 'Family notified', 'Staff guidance added'];
        $languageOptions = ['English', 'Welsh', 'British Sign Language', 'Other', 'Unknown'];
        $communicationMethodOptions = ['Verbal', 'Written', 'Phone call', 'SMS', 'Email', 'Picture cards', 'Easy Read', 'BSL', 'Interpreter', 'Family representative'];
        $communicationAidOptions = ['Glasses', 'Hearing aid', 'Communication board', 'Large print', 'Easy Read', 'Braille', 'Picture cards', 'Translation support', 'None'];
        $genderOptions = ['Female', 'Male', 'Non-binary', 'Other', 'Prefer not to say', 'Not recorded'];
        $ethnicityOptions = ['White British', 'White Irish', 'Other White', 'Black African', 'Black Caribbean', 'Other Black', 'Indian', 'Pakistani', 'Bangladeshi', 'Chinese', 'Mixed ethnic background', 'Other ethnic group', 'Prefer not to say', 'Not recorded'];
        $religionOptions = ['No religion', 'Christian', 'Muslim', 'Hindu', 'Sikh', 'Jewish', 'Buddhist', 'Other religion', 'Prefer not to say', 'Not recorded'];
        $disabilityOptions = ['No disability', 'Physical disability', 'Sensory impairment', 'Learning disability', 'Mental health condition', 'Long-term health condition', 'Multiple disabilities', 'Prefer not to say', 'Not recorded'];
        $sexualOrientationOptions = ['Heterosexual', 'Gay/Lesbian', 'Bisexual', 'Other', 'Prefer not to say', 'Not recorded'];
        $reasonableAdjustmentOptions = ['Large print', 'Easy Read', 'Interpreter', 'BSL support', 'Longer appointment time', 'Accessible transport', 'Wheelchair access', 'Carer/family present', 'Quiet environment', 'None required'];
        $livingArrangementOptions = ['Lives alone', 'Lives with spouse/partner', 'Lives with family', 'Supported living', 'Residential care', 'Nursing care', 'Temporary accommodation', 'Homeless/no fixed abode'];
        $socialIsolationOptions = ['None', 'Low', 'Medium', 'High', 'Not assessed'];
        $employmentOptions = ['Employed', 'Self-employed', 'Unemployed', 'Retired', 'Student', 'Unable to work due to health', 'Not recorded'];
        $familySupportOptions = ['Strong support', 'Some support', 'Limited support', 'No support', 'Unknown'];
        $communityEngagementOptions = ['Active', 'Occasional', 'Limited', 'None', 'Unknown'];
        $financialConcernOptions = ['None reported', 'Benefits support needed', 'Debt concerns', 'Difficulty paying bills', 'Appointee/deputy involved', 'Unknown'];
        $homeConditionOptions = ['Good', 'Fair', 'Poor', 'Unsafe', 'Not assessed'];
        $accessibilityOptions = ['Fully accessible', 'Minor adaptations needed', 'Major adaptations needed', 'Not accessible', 'Not assessed'];
        $cleanlinessOptions = ['Good', 'Acceptable', 'Poor', 'Severe concern', 'Not assessed'];
        $safetyHazardOptions = ['Trip hazards', 'Poor lighting', 'Unsafe flooring', 'Clutter/hoarding', 'Unsafe electrics', 'Fire hazards', 'No heating', 'Pest concerns', 'Unsafe stairs', 'No hazards identified'];
        $equipmentOptions = ['Walking aid', 'Wheelchair', 'Hospital bed', 'Hoist', 'Shower chair', 'Grab rails', 'Raised toilet seat', 'Pressure mattress', 'Pendant alarm', 'Medication dispenser', 'None required'];
    @endphp

    <x-page-header title="{{ $client->fullName() }} Onboarding" description="Complete assessment evidence, submit for verification, then approve or decline with clear review notes.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge {{ $statusBadge }} d-inline-flex align-items-center px-3">{{ ucfirst($status) }}</span>
                <a class="btn btn-outline-secondary" href="{{ route('clients.index') }}"><i class="fa-solid fa-arrow-left me-1"></i>Clients</a>
            </div>
        </x-slot:action>
    </x-page-header>

    @if ($client->review_notes)
        <div class="alert alert-warning">
            <strong>Review notes:</strong> {{ $client->review_notes }}
        </div>
    @endif

    <div class="assessment-layout">
        <aside class="assessment-steps">
            <p class="section-kicker mb-2">Onboarding steps</p>
            <button type="button" data-step-target="0">Master record</button>
            <button type="button" data-step-target="1">1. Needs</button>
            <button type="button" data-step-target="2">2. Functional</button>
            <button type="button" data-step-target="3">3. Medical</button>
            <button type="button" data-step-target="4">4. Mental capacity</button>
            <button type="button" data-step-target="5">5. Risk</button>
            <button type="button" data-step-target="6">6. Communication</button>
            <button type="button" data-step-target="7">7. Equality</button>
            <button type="button" data-step-target="8">8. Social</button>
            <button type="button" data-step-target="9">9. Environmental</button>
        </aside>

        <form class="form-workspace" method="POST" action="{{ route('clients.assessments.update', $client) }}" data-assessment-stepper>
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

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-master">
                <div class="form-section-header">
                    <span class="section-kicker">General master record</span>
                    <h2 class="form-section-title mt-2">Assessment Details</h2>
                    <p class="form-section-description">Record who assessed the client, the overall risk picture, and the next review point.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="assessment_date">Assessment date</label>
                        <input class="form-control focus-ring-brand" id="assessment_date" name="assessment[assessment_date]" type="date" value="{{ old('assessment.assessment_date', $assessment->assessment_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="assessment_type">Assessment type</label>
                        <select class="form-select focus-ring-brand" id="assessment_type" name="assessment[assessment_type]" required>
                            <option value="initial" @selected(old('assessment.assessment_type', $assessment->assessment_type) === 'initial')>Initial</option>
                            <option value="review" @selected(old('assessment.assessment_type', $assessment->assessment_type) === 'review')>Review</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="assessor_name">Assessor name</label>
                        <input class="form-control focus-ring-brand" id="assessor_name" name="assessment[assessor_name]" value="{{ old('assessment.assessor_name', $assessment->assessor_name) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="overall_risk_level">Overall risk level</label>
                        <select class="form-select focus-ring-brand" id="overall_risk_level" name="assessment[overall_risk_level]">
                            <option value="">Select risk</option>
                            @foreach ($riskLevelValues as $value => $label)
                                <option value="{{ $value }}" @selected(old('assessment.overall_risk_level', $assessment->overall_risk_level) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="overall_summary">Overall summary</label>
                        <textarea class="form-control focus-ring-brand" id="overall_summary" name="assessment[overall_summary]" rows="4">{{ old('assessment.overall_summary', $assessment->overall_summary) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="recommendations">Recommendations</label>
                        <textarea class="form-control focus-ring-brand" id="recommendations" name="assessment[recommendations]" rows="4">{{ old('assessment.recommendations', $assessment->recommendations) }}</textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="next_review_date">Next review</label>
                        <input class="form-control focus-ring-brand" id="next_review_date" name="assessment[next_review_date]" type="date" value="{{ old('assessment.next_review_date', $assessment->next_review_date?->format('Y-m-d')) }}">
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-needs">
                <div class="form-section-header">
                    <span class="section-kicker">Step 1</span>
                    <h2 class="form-section-title mt-2">Needs Assessment</h2>
                    <p class="form-section-description">Capture the care needs that shape the support plan and priority actions.</p>
                </div>
                <div class="row g-3">
                    @foreach ([
                        'physical_needs' => 'Physical needs',
                        'psychological_needs' => 'Psychological needs',
                        'social_needs' => 'Social needs',
                        'spiritual_needs' => 'Spiritual needs',
                        'environmental_needs' => 'Environmental needs',
                        'priority_needs' => 'Priority needs',
                    ] as $field => $label)
                        <div class="col-md-6">
                            <label class="form-label" for="needs_{{ $field }}">{{ $label }}</label>
                            <select class="form-select focus-ring-brand" id="needs_{{ $field }}" name="needs[{{ $field }}]">
                                <option value="">Select need</option>
                                @foreach ($needsOptions[$field] as $option)
                                    <option value="{{ $option }}" @selected(old('needs.'.$field, $assessment->needs?->{$field}) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-12">
                        <label class="form-label" for="needs_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="needs_notes" name="needs[notes]" rows="3">{{ old('needs.notes', $assessment->needs?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-functional">
                <div class="form-section-header">
                    <span class="section-kicker">Step 2</span>
                    <h2 class="form-section-title mt-2">Functional Assessment</h2>
                    <p class="form-section-description">Record daily living ability and independence level.</p>
                </div>
                <div class="row g-3">
                    @foreach ([
                        'mobility_status' => 'Mobility status',
                        'bathing_ability' => 'Bathing ability',
                        'dressing_ability' => 'Dressing ability',
                        'eating_ability' => 'Eating ability',
                        'toileting_ability' => 'Toileting ability',
                        'transferring_ability' => 'Transferring ability',
                    ] as $field => $label)
                        <div class="col-md-3">
                            <label class="form-label" for="functional_{{ $field }}">{{ $label }}</label>
                            <select class="form-select focus-ring-brand" id="functional_{{ $field }}" name="functional[{{ $field }}]">
                                <option value="">Select</option>
                                @foreach ($supportLevels as $option)
                                    <option value="{{ $option }}" @selected(old('functional.'.$field, $assessment->functional?->{$field}) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-3">
                        <label class="form-label" for="functional_continence_status">Continence status</label>
                        <select class="form-select focus-ring-brand" id="functional_continence_status" name="functional[continence_status]">
                            <option value="">Select</option>
                            @foreach ($continenceOptions as $option)
                                <option value="{{ $option }}" @selected(old('functional.continence_status', $assessment->functional?->continence_status) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="functional_independence_level">Independence level</label>
                        <select class="form-select focus-ring-brand" id="functional_independence_level" name="functional[independence_level]">
                            <option value="">Select</option>
                            @foreach ($independenceOptions as $option)
                                <option value="{{ $option }}" @selected(old('functional.independence_level', $assessment->functional?->independence_level) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="functional_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="functional_notes" name="functional[notes]" rows="3">{{ old('functional.notes', $assessment->functional?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-medical">
                <div class="form-section-header">
                    <span class="section-kicker">Step 3</span>
                    <h2 class="form-section-title mt-2">Medical Assessment</h2>
                    <p class="form-section-description">Capture clinical context and medication support requirements before care delivery starts.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="medical_diagnoses">Diagnoses / conditions</label>
                        <select class="form-select focus-ring-brand" id="medical_diagnoses" name="medical[diagnoses]">
                            <option value="">Select diagnosis</option>
                            @foreach ($diagnosisOptions as $option)
                                <option value="{{ $option }}" @selected(old('medical.diagnoses', $assessment->medical?->diagnoses) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="medical_medical_conditions">Medical conditions</label>
                        <select class="form-select focus-ring-brand" id="medical_medical_conditions" name="medical[medical_conditions]">
                            <option value="">Select condition</option>
                            @foreach ($diagnosisOptions as $option)
                                <option value="{{ $option }}" @selected(old('medical.medical_conditions', $assessment->medical?->medical_conditions) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="medical_medications">Medication support</label>
                        <select class="form-select focus-ring-brand" id="medical_medications" name="medical[medications]">
                            <option value="">Select medication support</option>
                            @foreach ($medicationSupportOptions as $option)
                                <option value="{{ $option }}" @selected(old('medical.medications', $assessment->medical?->medications) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="medical_allergies">Allergies</label>
                        <select class="form-select focus-ring-brand" id="medical_allergies" name="medical[allergies]">
                            <option value="">Select allergy status</option>
                            @foreach ($allergyOptions as $option)
                                <option value="{{ $option }}" @selected(old('medical.allergies', $assessment->medical?->allergies) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="medical_vital_signs">Vital signs</label>
                        <select class="form-select focus-ring-brand" id="medical_vital_signs" name="medical[vital_signs]">
                            <option value="">Select status</option>
                            @foreach (['Stable', 'Requires monitoring', 'Abnormal reading', 'Not assessed'] as $option)
                                <option value="{{ $option }}" @selected(old('medical.vital_signs', $assessment->medical?->vital_signs) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="medical_gp_details">GP details</label>
                        <select class="form-select focus-ring-brand" id="medical_gp_details" name="medical[gp_details]">
                            <option value="">Select GP detail needed</option>
                            @foreach ($gpDetailOptions as $option)
                                <option value="{{ $option }}" @selected(old('medical.gp_details', $assessment->medical?->gp_details) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="hidden" name="medical[medication_support_needed]" value="0">
                        <label class="choice-card" for="medication_support_needed">
                            <input class="form-check-input" id="medication_support_needed" name="medical[medication_support_needed]" type="checkbox" value="1" @checked((bool) old('medical.medication_support_needed', $assessment->medical?->medication_support_needed))>
                            <span><span class="d-block fw-bold">Medication support needed</span><span class="d-block text-secondary small">Client requires support, prompting, or administration checks.</span></span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="medical_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="medical_notes" name="medical[notes]" rows="3">{{ old('medical.notes', $assessment->medical?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-capacity">
                <div class="form-section-header">
                    <span class="section-kicker">Step 4</span>
                    <h2 class="form-section-title mt-2">Mental Capacity Assessment</h2>
                    <p class="form-section-description">Record decision-specific capacity evidence and any best-interest pathway.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="decision_type">Decision type</label>
                        <select class="form-select focus-ring-brand" id="decision_type" name="mental_capacity[decision_type]">
                            <option value="">Select decision type</option>
                            @foreach ($decisionTypeOptions as $option)
                                <option value="{{ $option }}" @selected(old('mental_capacity.decision_type', $assessment->mentalCapacity?->decision_type) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="capacity_outcome">Capacity outcome</label>
                        <select class="form-select focus-ring-brand" id="capacity_outcome" name="mental_capacity[capacity_outcome]">
                            <option value="">Select outcome</option>
                            @foreach ($capacityOutcomeOptions as $option)
                                <option value="{{ $option }}" @selected(old('mental_capacity.capacity_outcome', $assessment->mentalCapacity?->capacity_outcome) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="dols_lps_status">DoLS/LPS status</label>
                        <select class="form-select focus-ring-brand" id="dols_lps_status" name="mental_capacity[dols_lps_status]">
                            <option value="">Select status</option>
                            @foreach ($dolsLpsOptions as $option)
                                <option value="{{ $option }}" @selected(old('mental_capacity.dols_lps_status', $assessment->mentalCapacity?->dols_lps_status) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ([
                        'understands_information' => 'Understands information',
                        'retains_information' => 'Retains information',
                        'weighs_information' => 'Weighs information',
                        'communicates_decision' => 'Communicates decision',
                        'imca_involved' => 'IMCA involved',
                    ] as $field => $label)
                        <div class="col-md-4">
                            <input type="hidden" name="mental_capacity[{{ $field }}]" value="0">
                            <label class="choice-card" for="mental_capacity_{{ $field }}">
                                <input class="form-check-input" id="mental_capacity_{{ $field }}" name="mental_capacity[{{ $field }}]" type="checkbox" value="1" @checked((bool) old('mental_capacity.'.$field, $assessment->mentalCapacity?->{$field}))>
                                <span class="fw-bold">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label class="form-label" for="best_interest_decision">Best-interest decision</label>
                        <select class="form-select focus-ring-brand" id="best_interest_decision" name="mental_capacity[best_interest_decision]">
                            <option value="">Select decision status</option>
                            @foreach ($bestInterestOptions as $option)
                                <option value="{{ $option }}" @selected(old('mental_capacity.best_interest_decision', $assessment->mentalCapacity?->best_interest_decision) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="mental_capacity_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="mental_capacity_notes" name="mental_capacity[notes]" rows="3">{{ old('mental_capacity.notes', $assessment->mentalCapacity?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-risk">
                <div class="form-section-header">
                    <span class="section-kicker">Step 5</span>
                    <h2 class="form-section-title mt-2">Risk Assessment</h2>
                    <p class="form-section-description">Identify risks and the controls needed before care starts.</p>
                </div>
                <div class="row g-3">
                    @foreach ([
                        'falls_risk' => 'Falls risk',
                        'pressure_ulcer_risk' => 'Pressure ulcer risk',
                        'manual_handling_risk' => 'Manual handling risk',
                        'environmental_risk' => 'Environmental risk',
                        'behaviour_risk' => 'Behaviour risk',
                        'safeguarding_risk' => 'Safeguarding risk',
                    ] as $field => $label)
                        <div class="col-md-4">
                            <label class="form-label" for="risk_{{ $field }}">{{ $label }}</label>
                            <select class="form-select focus-ring-brand" id="risk_{{ $field }}" name="risk[{{ $field }}]">
                                <option value="">Select risk</option>
                                @foreach ($riskLevels as $option)
                                    <option value="{{ $option }}" @selected(old('risk.'.$field, $assessment->risk?->{$field}) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label class="form-label" for="control_measures">Control measures</label>
                        <select class="form-select focus-ring-brand" id="control_measures" name="risk[control_measures]">
                            <option value="">Select control measure</option>
                            @foreach ($controlMeasureOptions as $option)
                                <option value="{{ $option }}" @selected(old('risk.control_measures', $assessment->risk?->control_measures) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="risk_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="risk_notes" name="risk[notes]" rows="3">{{ old('risk.notes', $assessment->risk?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-communication">
                <div class="form-section-header">
                    <span class="section-kicker">Step 6</span>
                    <h2 class="form-section-title mt-2">Communication Assessment</h2>
                    <p class="form-section-description">Record communication needs so staff can safely understand and be understood.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="preferred_language">Preferred language</label>
                        <select class="form-select focus-ring-brand" id="preferred_language" name="communication[preferred_language]">
                            <option value="">Select language</option>
                            @foreach ($languageOptions as $option)
                                <option value="{{ $option }}" @selected(old('communication.preferred_language', $assessment->communication?->preferred_language) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="communication_method">Communication method</label>
                        <select class="form-select focus-ring-brand" id="communication_method" name="communication[communication_method]">
                            <option value="">Select method</option>
                            @foreach ($communicationMethodOptions as $option)
                                <option value="{{ $option }}" @selected(old('communication.communication_method', $assessment->communication?->communication_method) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ([
                        'hearing_impairment' => 'Hearing impairment',
                        'vision_impairment' => 'Vision impairment',
                        'speech_difficulty' => 'Speech difficulty',
                        'interpreter_required' => 'Interpreter required',
                    ] as $field => $label)
                        <div class="col-md-3">
                            <input type="hidden" name="communication[{{ $field }}]" value="0">
                            <label class="choice-card" for="communication_{{ $field }}">
                                <input class="form-check-input" id="communication_{{ $field }}" name="communication[{{ $field }}]" type="checkbox" value="1" @checked((bool) old('communication.'.$field, $assessment->communication?->{$field}))>
                                <span class="fw-bold">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label class="form-label" for="communication_aids">Communication aids</label>
                        <select class="form-select focus-ring-brand" id="communication_aids" name="communication[communication_aids]">
                            <option value="">Select aid</option>
                            @foreach ($communicationAidOptions as $option)
                                <option value="{{ $option }}" @selected(old('communication.communication_aids', $assessment->communication?->communication_aids) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="communication_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="communication_notes" name="communication[notes]" rows="3">{{ old('communication.notes', $assessment->communication?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-equality">
                <div class="form-section-header">
                    <span class="section-kicker">Step 7</span>
                    <h2 class="form-section-title mt-2">Equality Assessment</h2>
                    <p class="form-section-description">Record protected characteristics and adjustments needed for equitable care.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="equality_gender">Gender</label>
                        <select class="form-select focus-ring-brand" id="equality_gender" name="equality[gender]">
                            <option value="">Select gender</option>
                            @foreach ($genderOptions as $gender)
                                <option value="{{ $gender }}" @selected(old('equality.gender', $assessment->equality?->gender) === $gender)>{{ $gender }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ([
                        'ethnicity' => ['Ethnicity', $ethnicityOptions],
                        'religion' => ['Religion', $religionOptions],
                        'disability_status' => ['Disability status', $disabilityOptions],
                        'sexual_orientation' => ['Sexual orientation', $sexualOrientationOptions],
                    ] as $field => [$label, $options])
                        <div class="col-md-4">
                            <label class="form-label" for="equality_{{ $field }}">{{ $label }}</label>
                            <select class="form-select focus-ring-brand" id="equality_{{ $field }}" name="equality[{{ $field }}]">
                                <option value="">Select</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option }}" @selected(old('equality.'.$field, $assessment->equality?->{$field}) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-4">
                        <label class="form-label" for="reasonable_adjustments">Reasonable adjustments</label>
                        <select class="form-select focus-ring-brand" id="reasonable_adjustments" name="equality[reasonable_adjustments]">
                            <option value="">Select adjustment</option>
                            @foreach ($reasonableAdjustmentOptions as $option)
                                <option value="{{ $option }}" @selected(old('equality.reasonable_adjustments', $assessment->equality?->reasonable_adjustments) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cultural_needs">Cultural needs</label>
                        <textarea class="form-control focus-ring-brand" id="cultural_needs" name="equality[cultural_needs]" rows="3">{{ old('equality.cultural_needs', $assessment->equality?->cultural_needs) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="equality_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="equality_notes" name="equality[notes]" rows="3">{{ old('equality.notes', $assessment->equality?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-social">
                <div class="form-section-header">
                    <span class="section-kicker">Step 8</span>
                    <h2 class="form-section-title mt-2">Social Assessment</h2>
                    <p class="form-section-description">Capture family, community, social isolation, employment, and financial context.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="living_arrangements">Living arrangements</label>
                        <select class="form-select focus-ring-brand" id="living_arrangements" name="social[living_arrangements]">
                            <option value="">Select arrangement</option>
                            @foreach ($livingArrangementOptions as $option)
                                <option value="{{ $option }}" @selected(old('social.living_arrangements', $assessment->social?->living_arrangements) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="social_isolation_risk">Social isolation risk</label>
                        <select class="form-select focus-ring-brand" id="social_isolation_risk" name="social[social_isolation_risk]">
                            <option value="">Select risk</option>
                            @foreach ($socialIsolationOptions as $option)
                                <option value="{{ $option }}" @selected(old('social.social_isolation_risk', $assessment->social?->social_isolation_risk) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="employment_status">Employment status</label>
                        <select class="form-select focus-ring-brand" id="employment_status" name="social[employment_status]">
                            <option value="">Select status</option>
                            @foreach ($employmentOptions as $option)
                                <option value="{{ $option }}" @selected(old('social.employment_status', $assessment->social?->employment_status) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ([
                        'family_support' => ['Family support', $familySupportOptions],
                        'community_engagement' => ['Community engagement', $communityEngagementOptions],
                        'financial_concerns' => ['Financial concerns', $financialConcernOptions],
                    ] as $field => [$label, $options])
                        <div class="col-md-6">
                            <label class="form-label" for="social_{{ $field }}">{{ $label }}</label>
                            <select class="form-select focus-ring-brand" id="social_{{ $field }}" name="social[{{ $field }}]">
                                <option value="">Select</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option }}" @selected(old('social.'.$field, $assessment->social?->{$field}) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label class="form-label" for="social_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="social_notes" name="social[notes]" rows="3">{{ old('social.notes', $assessment->social?->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section assessment-step-panel" data-step-panel id="assessment-environmental">
                <div class="form-section-header">
                    <span class="section-kicker">Step 9</span>
                    <h2 class="form-section-title mt-2">Environmental Assessment</h2>
                    <p class="form-section-description">Record home safety, accessibility, equipment, fire, and cleanliness factors.</p>
                </div>
                <div class="row g-3">
                    @foreach ([
                        'home_condition' => ['Home condition', $homeConditionOptions],
                        'accessibility' => ['Accessibility', $accessibilityOptions],
                        'fire_risk' => ['Fire risk', $riskLevels],
                        'cleanliness_level' => ['Cleanliness level', $cleanlinessOptions],
                    ] as $field => [$label, $options])
                        <div class="col-md-3">
                            <label class="form-label" for="environmental_{{ $field }}">{{ $label }}</label>
                            <select class="form-select focus-ring-brand" id="environmental_{{ $field }}" name="environmental[{{ $field }}]">
                                <option value="">Select</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option }}" @selected(old('environmental.'.$field, $assessment->environmental?->{$field}) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    @foreach ([
                        'safety_hazards' => ['Safety hazards', $safetyHazardOptions],
                        'equipment_needed' => ['Equipment needed', $equipmentOptions],
                    ] as $field => [$label, $options])
                        <div class="col-md-4">
                            <label class="form-label" for="environmental_{{ $field }}">{{ $label }}</label>
                            <select class="form-select focus-ring-brand" id="environmental_{{ $field }}" name="environmental[{{ $field }}]">
                                <option value="">Select</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option }}" @selected(old('environmental.'.$field, $assessment->environmental?->{$field}) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-4">
                        <label class="form-label" for="environmental_notes">Notes</label>
                        <textarea class="form-control focus-ring-brand" id="environmental_notes" name="environmental[notes]" rows="3">{{ old('environmental.notes', $assessment->environmental?->notes) }}</textarea>
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
                    <a class="btn btn-outline-secondary fw-semibold" href="{{ route('clients.index') }}">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
            <div>
                <h2 class="h5 fw-bold mb-1">Verification And Adjudication</h2>
                <p class="text-secondary mb-0">Submit once the assessment evidence is ready. Pending records can be approved or declined with review notes.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('clients.assessments.submit', $client) }}" data-confirm data-confirm-title="Submit onboarding?" data-confirm-text="This will move the client into pending review." data-confirm-button="Yes, submit">
                    @csrf
                    <button class="btn btn-action btn-action-primary" type="submit"><i class="fa-solid fa-paper-plane"></i>Submit for review</button>
                </form>
                @if ($status === 'pending')
                    <form method="POST" action="{{ route('clients.assessments.approve', $client) }}" data-confirm data-confirm-title="Approve onboarding?" data-confirm-text="This client onboarding record will be approved." data-confirm-button="Yes, approve">
                        @csrf
                        <button class="btn btn-action btn-action-primary" type="submit"><i class="fa-solid fa-check"></i>Approve</button>
                    </form>
                @endif
            </div>
        </div>
        @if ($status === 'pending')
            <div class="card-footer bg-white">
                <form method="POST" action="{{ route('clients.assessments.decline', $client) }}" data-confirm data-confirm-title="Decline onboarding?" data-confirm-text="The review notes will be sent back for correction and resubmission." data-confirm-button="Yes, decline">
                    @csrf
                    <label class="form-label" for="review_notes">Decline notes</label>
                    <textarea class="form-control focus-ring-brand" id="review_notes" name="review_notes" rows="3" required placeholder="Explain what must be reviewed before resubmission.">{{ old('review_notes') }}</textarea>
                    <div class="mt-3 d-flex justify-content-end">
                        <button class="btn btn-action btn-action-danger" type="submit"><i class="fa-solid fa-ban"></i>Decline and request review</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
