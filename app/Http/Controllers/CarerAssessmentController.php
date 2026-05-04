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

            $carer->update([
                'job_title' => CarerProfile::JOB_TITLES[$attributes['job_title']] ?? 'Carer',
                'home_id' => $attributes['assigned_home_id'],
                'is_active' => ($attributes['account_status'] ?? null) === 'active',
            ]);
        });

        return redirect()->route('carers.assessments.edit', $carer)->with('status', 'Carer assessment saved.');
    }

    public function submit(User $carer): RedirectResponse
    {
        $this->ensureCarer($carer);

        $profile = $this->profileFor($carer);

        if (! $this->hasCompletedIdentity($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete Identity & Legal before submitting the carer assessment.']);
        }

        if (! $this->hasCompletedContact($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete Contact & Emergency before submitting the carer assessment.']);
        }

        if (! $this->hasCompletedEmployment($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete Employment & Role before submitting the carer assessment.']);
        }

        if (! $this->hasCompletedSafeguarding($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete Safeguarding & Compliance before submitting the carer assessment.']);
        }

        if (! $this->hasCompletedTraining($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete all mandatory Training & Qualifications before submitting the carer assessment.']);
        }

        if (! $this->hasCompletedHealth($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete Health & Fitness to Work before submitting the carer assessment.']);
        }

        if (! $this->hasCompletedAvailability($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete Availability & Scheduling before submitting the carer assessment.']);
        }

        if (! $this->hasCompletedGdpr($profile)) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Complete GDPR & Consent before submitting the carer assessment.']);
        }

        $criticalFailures = $profile->load('trainingRecords')->criticalValidationFailures();

        if ($criticalFailures !== []) {
            return redirect()
                ->route('carers.assessments.edit', $carer)
                ->withErrors(['assessment' => 'Critical validation failed: '.implode(' ', $criticalFailures)]);
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
                ->withErrors(['assessment' => 'Cannot approve until critical validation passes: '.implode(' ', $criticalFailures)]);
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

    private function hasCompletedIdentity(CarerProfile $profile): bool
    {
        $required = [
            'legal_name',
            'date_of_birth',
            'national_insurance_number',
            'photo_id_type',
            'id_document_number',
            'right_to_work_status',
        ];

        if (collect($required)->contains(fn (string $field): bool => blank($profile->{$field}))) {
            return false;
        }

        return $profile->right_to_work_status === 'uk_citizen' || filled($profile->visa_status);
    }

    private function hasCompletedContact(CarerProfile $profile): bool
    {
        $required = [
            'address_line_1',
            'city',
            'postcode',
            'contact_phone',
            'contact_email',
            'emergency_contact_name',
            'emergency_contact_phone',
        ];

        return ! collect($required)->contains(fn (string $field): bool => blank($profile->{$field}));
    }

    private function hasCompletedEmployment(CarerProfile $profile): bool
    {
        $required = [
            'job_title',
            'employment_type',
            'start_date',
            'assigned_home_id',
        ];

        return ! collect($required)->contains(fn (string $field): bool => blank($profile->{$field}));
    }

    private function hasCompletedSafeguarding(CarerProfile $profile): bool
    {
        if (blank($profile->dbs_check_status) || blank($profile->safeguarding_training_completed)) {
            return false;
        }

        if ($profile->dbs_check_status === 'verified' && (blank($profile->dbs_certificate_number) || blank($profile->dbs_expiry_date))) {
            return false;
        }

        return $profile->safeguarding_training_completed !== 'yes' || filled($profile->last_safeguarding_training_date);
    }

    private function hasCompletedTraining(CarerProfile $profile): bool
    {
        $records = $profile->trainingRecords()->get()->keyBy('training_key');

        foreach (array_keys(CarerTrainingRecord::MANDATORY_TRAINING) as $trainingKey) {
            $record = $records->get($trainingKey);

            if (! $record || $record->status !== 'completed' || blank($record->expiry_date)) {
                return false;
            }
        }

        return true;
    }

    private function hasCompletedHealth(CarerProfile $profile): bool
    {
        return $profile->occupational_health_clearance === 'fit' && (bool) $profile->fit_to_work_declaration;
    }

    private function hasCompletedAvailability(CarerProfile $profile): bool
    {
        return filled($profile->availability_pattern)
            && filled($profile->max_weekly_hours)
            && filled($profile->shift_preference);
    }

    private function hasCompletedGdpr(CarerProfile $profile): bool
    {
        return (bool) $profile->data_processing_consent
            && (bool) $profile->privacy_policy_accepted
            && filled($profile->data_retention_category);
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
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function profileAttributes(UpdateCarerAssessmentRequest $request, array $validated, CarerProfile $existingProfile): array
    {
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

        $attributes['fit_to_work_declaration'] = (bool) ($validated['fit_to_work_declaration'] ?? false);
        $attributes['mfa_enabled'] = (bool) ($validated['mfa_enabled'] ?? false);
        $attributes['data_processing_consent'] = (bool) ($validated['data_processing_consent'] ?? false);
        $attributes['privacy_policy_accepted'] = (bool) ($validated['privacy_policy_accepted'] ?? false);
        $attributes['privacy_policy_version'] = config('privacy.policy_version', 'v1');
        $attributes['data_processing_consented_at'] = $attributes['data_processing_consent']
            ? ($existingProfile->data_processing_consented_at ?: now())
            : null;
        $attributes['privacy_policy_accepted_at'] = $attributes['privacy_policy_accepted']
            ? ($existingProfile->privacy_policy_accepted_at ?: now())
            : null;
        $attributes['skills'] = array_values($validated['skills'] ?? []);
        $attributes['languages'] = array_values($validated['languages'] ?? []);

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
