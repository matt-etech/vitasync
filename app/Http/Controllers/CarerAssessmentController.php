<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewCarerAssessmentRequest;
use App\Http\Requests\UpdateCarerAssessmentRequest;
use App\Models\CarerProfile;
use App\Models\CarerTrainingRecord;
use App\Models\Home;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CarerAssessmentController extends Controller
{
    public function edit(User $carer): View
    {
        $this->ensureCarer($carer);
        $profile = $this->profileFor($carer);

        return view('carers.assessments.edit', [
            'carer' => $carer->refresh()->load(['home', 'roles']),
            'profile' => $profile->load('trainingRecords'),
            'trainingRecords' => $this->trainingRecordsFor($profile),
            'homes' => Home::where('status', 'active')->orWhere('id', $carer->home_id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCarerAssessmentRequest $request, User $carer): RedirectResponse
    {
        $this->ensureCarer($carer);

        DB::transaction(function () use ($request, $carer): void {
            $profile = $this->profileFor($carer);
            $attributes = $this->profileAttributes($request, $request->validated(), $profile);

            $profile->update(array_merge($attributes, [
                'status' => CarerProfile::STATUS_ONBOARDING,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_notes' => null,
            ]));
            $this->syncTrainingRecords($request, $profile);

            $carerUpdates = [];

            if (array_key_exists('job_title', $attributes)) {
                $carerUpdates['job_title'] = CarerProfile::JOB_TITLES[$attributes['job_title']] ?? 'Carer';
            }

            if (array_key_exists('assigned_home_id', $attributes)) {
                $carerUpdates['home_id'] = $attributes['assigned_home_id'];
            }

            if (array_key_exists('account_status', $attributes)) {
                $carerUpdates['is_active'] = $attributes['account_status'] === 'active';
            }

            if ($carerUpdates !== []) {
                $carer->update($carerUpdates);
            }
        });

        return redirect()->route('carers.assessments.edit', $carer)->with('status', 'Carer assessment saved.');
    }

    public function submit(User $carer): RedirectResponse
    {
        $this->ensureCarer($carer);

        $profile = $this->profileFor($carer);

        $missingSections = collect([
            'Identity & Legal' => $this->missingIdentityFields($profile),
            'Contact & Emergency' => $this->missingContactFields($profile),
            'Employment & Role' => $this->missingEmploymentFields($profile),
            'Safeguarding & Compliance' => $this->missingSafeguardingFields($profile),
            'Training & Qualifications' => $this->missingTrainingFields($profile),
            'Health & Fitness to Work' => $this->missingHealthFields($profile),
            'Availability & Scheduling' => $this->missingAvailabilityFields($profile),
            'GDPR & Consent' => $this->missingGdprFields($profile),
        ])->filter()->all();

        if ($missingSections !== []) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors($this->missingFieldErrors($profile));
        }

        $criticalFailures = $profile->load('trainingRecords')->criticalValidationFailures();

        if ($criticalFailures !== []) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors($this->criticalFieldErrors($profile));
        }

        DB::transaction(function () use ($carer): void {
            $profile = $this->profileFor($carer);

            $profile->update([
                'status' => CarerProfile::STATUS_PENDING,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_notes' => null,
            ]);

            app(AuditLogger::class)->log('carer_assessment_submitted', [
                'auditable' => $profile,
                'event' => 'CarerProfile',
                'metadata' => [
                    'carer_id' => $carer->id,
                ],
            ]);
        });

        return redirect()->route('carers.assessments.edit', $carer)->with('status', 'Carer onboarding submitted for review.');
    }

    public function approve(User $carer): RedirectResponse
    {
        $this->ensureCarer($carer);

        $profile = $this->profileFor($carer)->load('trainingRecords');
        $criticalFailures = $profile->criticalValidationFailures();

        if ($criticalFailures !== []) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors($this->criticalFieldErrors($profile));
        }

        DB::transaction(function () use ($carer): void {
            $profile = $this->profileFor($carer);

            $profile->update([
                'status' => CarerProfile::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => null,
            ]);

            app(AuditLogger::class)->log('carer_assessment_approved', [
                'auditable' => $profile,
                'event' => 'CarerProfile',
                'metadata' => [
                    'carer_id' => $carer->id,
                ],
            ]);
        });

        return redirect()->route('carers.index')->with('status', 'Carer onboarding approved.');
    }

    public function decline(ReviewCarerAssessmentRequest $request, User $carer): RedirectResponse
    {
        $this->ensureCarer($carer);

        DB::transaction(function () use ($request, $carer): void {
            $profile = $this->profileFor($carer);
            $notes = $request->validated('review_notes');

            $profile->update([
                'status' => CarerProfile::STATUS_DECLINED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => $notes,
            ]);

            app(AuditLogger::class)->log('carer_assessment_declined', [
                'auditable' => $profile,
                'event' => 'CarerProfile',
                'metadata' => [
                    'carer_id' => $carer->id,
                ],
                'new_values' => [
                    'review_notes' => $notes,
                ],
            ]);
        });

        return redirect()->route('carers.assessments.edit', $carer)->with('status', 'Carer onboarding declined with review notes.');
    }

    private function ensureCarer(User $carer): void
    {
        abort_unless($carer->roles()->where('name', 'Carer')->exists() || $carer->job_title === 'Carer', 404);
    }

    private function profileFor(User $carer): CarerProfile
    {
        return $carer->carerProfile()->firstOrCreate([], [
            'status' => CarerProfile::STATUS_ONBOARDING,
        ]);
    }

    /**
     * @return list<string>
     */
    private function missingIdentityFields(CarerProfile $profile): array
    {
        $missing = $this->missingProfileFields($profile, [
            'legal_name' => 'Full legal name',
            'date_of_birth' => 'Date of birth',
            'national_insurance_number' => 'National Insurance number',
            'photo_id_type' => 'Photo ID type',
            'id_document_number' => 'ID document number',
            'id_document_path' => 'ID document upload',
            'right_to_work_status' => 'Right to work status',
        ]);

        if ($profile->right_to_work_status !== 'uk_citizen' && blank($profile->visa_status)) {
            $missing[] = 'Visa status';
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    private function missingContactFields(CarerProfile $profile): array
    {
        return $this->missingProfileFields($profile, [
            'address_line_1' => 'Address line 1',
            'city' => 'City',
            'postcode' => 'Postcode',
            'contact_phone' => 'Phone',
            'contact_email' => 'Email',
            'emergency_contact_name' => 'Emergency contact name',
            'emergency_contact_phone' => 'Emergency contact phone',
        ]);
    }

    /**
     * @return list<string>
     */
    private function missingEmploymentFields(CarerProfile $profile): array
    {
        return $this->missingProfileFields($profile, [
            'job_title' => 'Job title',
            'employment_type' => 'Employment type',
            'start_date' => 'Start date',
            'assigned_home_id' => 'Assigned home/location',
        ]);
    }

    /**
     * @return list<string>
     */
    private function missingSafeguardingFields(CarerProfile $profile): array
    {
        $missing = $this->missingProfileFields($profile, [
            'dbs_check_status' => 'DBS check status',
            'safeguarding_training_completed' => 'Safeguarding training completed',
        ]);

        if ($profile->dbs_check_status === 'verified') {
            $missing = array_merge($missing, $this->missingProfileFields($profile, [
                'dbs_certificate_number' => 'DBS certificate number',
                'dbs_expiry_date' => 'DBS expiry date',
            ]));
        }

        if ($profile->safeguarding_training_completed === 'yes' && blank($profile->last_safeguarding_training_date)) {
            $missing[] = 'Last safeguarding training date';
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    private function missingTrainingFields(CarerProfile $profile): array
    {
        $missing = [];
        $records = $profile->trainingRecords()->get()->keyBy('training_key');

        foreach (CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName) {
            $record = $records->get($trainingKey);

            if (! $record) {
                $missing[] = "{$trainingName} training status";
                $missing[] = "{$trainingName} training expiry date";

                continue;
            }

            if ($record->status !== 'completed') {
                $missing[] = "{$trainingName} training status";
            }

            if (blank($record->expiry_date)) {
                $missing[] = "{$trainingName} training expiry date";
            }
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    private function missingHealthFields(CarerProfile $profile): array
    {
        $missing = [];

        if ($profile->occupational_health_clearance !== 'fit') {
            $missing[] = 'Occupational health clearance';
        }

        if (! $profile->fit_to_work_declaration) {
            $missing[] = 'Fit-to-work declaration';
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    private function missingAvailabilityFields(CarerProfile $profile): array
    {
        return $this->missingProfileFields($profile, [
            'availability_pattern' => 'Availability pattern',
            'max_weekly_hours' => 'Max weekly hours',
            'shift_preference' => 'Shift preference',
        ]);
    }

    /**
     * @return list<string>
     */
    private function missingGdprFields(CarerProfile $profile): array
    {
        $missing = [];

        if (! $profile->data_processing_consent) {
            $missing[] = 'Consent to data processing';
        }

        if (! $profile->privacy_policy_accepted) {
            $missing[] = 'Privacy policy accepted';
        }

        if (blank($profile->data_retention_category)) {
            $missing[] = 'Data retention category';
        }

        return $missing;
    }

    /**
     * @param  array<string, string>  $fields
     * @return list<string>
     */
    private function missingProfileFields(CarerProfile $profile, array $fields): array
    {
        return collect($fields)
            ->filter(fn (string $label, string $field): bool => blank($profile->{$field}))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, list<string>>  $missingSections
     */
    private function missingSectionsMessage(array $missingSections): string
    {
        $sections = collect($missingSections)
            ->map(fn (array $fields, string $section): string => "{$section}: ".implode(', ', $fields))
            ->implode('; ');

        return "Complete the missing assessment fields before submitting. Missing: {$sections}.";
    }

    /**
     * @return array<string, string>
     */
    private function missingFieldErrors(CarerProfile $profile): array
    {
        $errors = [];
        $records = $profile->trainingRecords()->get()->keyBy('training_key');

        foreach ([
            'legal_name' => 'Enter the full legal name.',
            'date_of_birth' => 'Enter the date of birth.',
            'national_insurance_number' => 'Enter the National Insurance number.',
            'photo_id_type' => 'Select the photo ID type.',
            'id_document_number' => 'Enter the ID document number.',
            'right_to_work_status' => 'Select the right to work status.',
            'address_line_1' => 'Enter address line 1.',
            'city' => 'Enter the city.',
            'postcode' => 'Enter the postcode.',
            'contact_phone' => 'Enter the phone number.',
            'contact_email' => 'Enter the email address.',
            'emergency_contact_name' => 'Enter the emergency contact name.',
            'emergency_contact_phone' => 'Enter the emergency contact phone.',
            'job_title' => 'Select the job title.',
            'employment_type' => 'Select the employment type.',
            'start_date' => 'Enter the start date.',
            'assigned_home_id' => 'Assign the carer to an active home.',
            'dbs_check_status' => 'Select the DBS check status.',
            'safeguarding_training_completed' => 'Confirm whether safeguarding training is completed.',
            'availability_pattern' => 'Select the availability pattern.',
            'max_weekly_hours' => 'Enter the maximum weekly hours.',
            'shift_preference' => 'Select the shift preference.',
            'data_retention_category' => 'Select the data retention category.',
        ] as $field => $message) {
            if (blank($profile->{$field})) {
                $errors[$field] = $message;
            }
        }

        if (blank($profile->id_document_path)) {
            $errors['id_document'] = 'Upload and save the ID document before submitting.';
        }

        if ($profile->right_to_work_status !== 'uk_citizen' && blank($profile->visa_status)) {
            $errors['visa_status'] = 'Select the visa status.';
        }

        if ($profile->dbs_check_status === 'verified') {
            if (blank($profile->dbs_certificate_number)) {
                $errors['dbs_certificate_number'] = 'Enter the DBS certificate number.';
            }

            if (blank($profile->dbs_expiry_date)) {
                $errors['dbs_expiry_date'] = 'Enter the DBS expiry date.';
            }
        }

        if ($profile->safeguarding_training_completed === 'yes' && blank($profile->last_safeguarding_training_date)) {
            $errors['last_safeguarding_training_date'] = 'Enter the last safeguarding training date.';
        }

        foreach (CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName) {
            $record = $records->get($trainingKey);

            if (! $record || $record->status !== 'completed') {
                $errors["trainings.{$trainingKey}.status"] = "Set {$trainingName} training to completed.";
            }

            if (! $record || blank($record->expiry_date)) {
                $errors["trainings.{$trainingKey}.expiry_date"] = "Enter the {$trainingName} training expiry date.";
            }
        }

        if ($profile->occupational_health_clearance !== 'fit') {
            $errors['occupational_health_clearance'] = 'Set occupational health clearance to Fit.';
        }

        if (! $profile->fit_to_work_declaration) {
            $errors['fit_to_work_declaration'] = 'Confirm the fit-to-work declaration.';
        }

        if (! $profile->data_processing_consent) {
            $errors['data_processing_consent'] = 'Consent to data processing is required.';
        }

        if (! $profile->privacy_policy_accepted) {
            $errors['privacy_policy_accepted'] = 'Privacy policy acceptance is required.';
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function criticalFieldErrors(CarerProfile $profile): array
    {
        $errors = [];
        $records = $profile->relationLoaded('trainingRecords')
            ? $profile->trainingRecords->keyBy('training_key')
            : $profile->trainingRecords()->get()->keyBy('training_key');

        if (blank($profile->assigned_home_id)) {
            $errors['assigned_home_id'] = 'Assign the carer to an active home.';
        }

        if ($profile->dbs_check_status !== 'verified') {
            $errors['dbs_check_status'] = 'DBS status must be verified.';
        }

        if ($profile->dbs_check_status === 'verified') {
            if (blank($profile->dbs_certificate_number)) {
                $errors['dbs_certificate_number'] = 'Enter the DBS certificate number.';
            }

            if (blank($profile->dbs_expiry_date)) {
                $errors['dbs_expiry_date'] = 'Enter the DBS expiry date.';
            }
        }

        if ($profile->safeguarding_training_completed !== 'yes') {
            $errors['safeguarding_training_completed'] = 'Safeguarding training must be completed.';
        }

        if (blank($profile->last_safeguarding_training_date)) {
            $errors['last_safeguarding_training_date'] = 'Enter the last safeguarding training date.';
        }

        foreach (CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName) {
            $record = $records->get($trainingKey);

            if (! $record || $record->status !== 'completed') {
                $errors["trainings.{$trainingKey}.status"] = "Set {$trainingName} training to completed.";
            }

            if (! $record || blank($record->expiry_date)) {
                $errors["trainings.{$trainingKey}.expiry_date"] = "Enter the {$trainingName} training expiry date.";
            }
        }

        if ($profile->occupational_health_clearance !== 'fit') {
            $errors['occupational_health_clearance'] = 'Set occupational health clearance to Fit.';
        }

        if (! $profile->fit_to_work_declaration) {
            $errors['fit_to_work_declaration'] = 'Confirm the fit-to-work declaration.';
        }

        if (! $profile->data_processing_consent) {
            $errors['data_processing_consent'] = 'Consent to data processing is required.';
        }

        if (! $profile->privacy_policy_accepted) {
            $errors['privacy_policy_accepted'] = 'Privacy policy acceptance is required.';
        }

        if ($errors !== []) {
            $errors['account_status'] = 'Resolve the highlighted readiness items before approval.';
        }

        return $errors;
    }

    /**
     * @return array<string, CarerTrainingRecord>
     */
    private function trainingRecordsFor(CarerProfile $profile): array
    {
        $existing = $profile->trainingRecords->keyBy('training_key');

        return collect(CarerTrainingRecord::MANDATORY_TRAINING)
            ->mapWithKeys(fn (string $trainingName, string $trainingKey): array => [
                $trainingKey => $existing->get($trainingKey) ?? new CarerTrainingRecord([
                    'training_key' => $trainingKey,
                    'training_name' => $trainingName,
                    'status' => 'not_started',
                ]),
            ])
            ->all();
    }

    private function syncTrainingRecords(UpdateCarerAssessmentRequest $request, CarerProfile $profile): void
    {
        $submittedTraining = $request->validated('trainings', []);

        if (! $request->has('trainings')) {
            return;
        }

        foreach (CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName) {
            $submittedRecord = $submittedTraining[$trainingKey] ?? [];
            $record = $profile->trainingRecords()->firstOrNew([
                'training_key' => $trainingKey,
            ]);

            $record->fill([
                'training_name' => $trainingName,
                'status' => $submittedRecord['status'] ?? 'not_started',
                'expiry_date' => ($submittedRecord['status'] ?? null) === 'completed'
                    ? ($submittedRecord['expiry_date'] ?? null)
                    : null,
            ]);

            if ($request->hasFile("trainings.{$trainingKey}.certificate")) {
                if ($record->certificate_path) {
                    Storage::disk('local')->delete($record->certificate_path);
                }

                $record->certificate_path = $request->file("trainings.{$trainingKey}.certificate")
                    ->store("carer-training-certificates/{$profile->user_id}");
            }

            $record->save();
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function profileAttributes(UpdateCarerAssessmentRequest $request, array $validated, CarerProfile $existingProfile): array
    {
        $submittedKeys = collect(array_keys($request->all()))->flip();
        $attributes = Arr::only($validated, [
            'legal_name',
            'date_of_birth',
            'national_insurance_number',
            'photo_id_type',
            'id_document_number',
            'right_to_work_status',
            'visa_status',
            'address_line_1',
            'address_line_2',
            'city',
            'postcode',
            'contact_phone',
            'contact_email',
            'emergency_contact_name',
            'emergency_contact_phone',
            'job_title',
            'employment_type',
            'start_date',
            'assigned_home_id',
            'dbs_check_status',
            'dbs_certificate_number',
            'dbs_expiry_date',
            'safeguarding_training_completed',
            'last_safeguarding_training_date',
            'occupational_health_clearance',
            'immunisation_status',
            'fit_to_work_declaration',
            'skills',
            'languages',
            'availability_pattern',
            'max_weekly_hours',
            'shift_preference',
            'account_status',
            'mfa_enabled',
            'data_processing_consent',
            'privacy_policy_accepted',
            'data_retention_category',
        ]);

        foreach (['fit_to_work_declaration', 'mfa_enabled', 'data_processing_consent', 'privacy_policy_accepted'] as $booleanField) {
            if ($submittedKeys->has($booleanField)) {
                $attributes[$booleanField] = (bool) ($validated[$booleanField] ?? false);
            }
        }

        $attributes['privacy_policy_version'] = config('privacy.policy_version', 'v1');

        $dataProcessingConsent = (bool) ($attributes['data_processing_consent'] ?? $existingProfile->data_processing_consent);
        $privacyPolicyAccepted = (bool) ($attributes['privacy_policy_accepted'] ?? $existingProfile->privacy_policy_accepted);

        $attributes['data_processing_consented_at'] = $dataProcessingConsent
            ? ($existingProfile->data_processing_consented_at ?: now())
            : null;
        $attributes['privacy_policy_accepted_at'] = $privacyPolicyAccepted
            ? ($existingProfile->privacy_policy_accepted_at ?: now())
            : null;

        if ($submittedKeys->has('skills')) {
            $attributes['skills'] = array_values($validated['skills'] ?? []);
        }

        if ($submittedKeys->has('languages')) {
            $attributes['languages'] = array_values($validated['languages'] ?? []);
        }

        if (($attributes['right_to_work_status'] ?? null) === 'uk_citizen') {
            $attributes['visa_status'] = 'na';
        }

        if (($attributes['dbs_check_status'] ?? null) !== 'verified') {
            $attributes['dbs_certificate_number'] = null;
            $attributes['dbs_expiry_date'] = null;
        }

        if (($attributes['safeguarding_training_completed'] ?? null) !== 'yes') {
            $attributes['last_safeguarding_training_date'] = null;
        }

        if ($request->hasFile('id_document')) {
            if ($existingProfile->id_document_path) {
                Storage::disk('local')->delete($existingProfile->id_document_path);
            }

            $attributes['id_document_path'] = $request->file('id_document')->store('carer-id-documents');
        }

        return $attributes;
    }
}
