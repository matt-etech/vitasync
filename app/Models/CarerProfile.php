<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'status',
    'legal_name',
    'date_of_birth',
    'national_insurance_number',
    'photo_id_type',
    'id_document_number',
    'id_document_path',
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
    'data_processing_consented_at',
    'privacy_policy_accepted',
    'privacy_policy_accepted_at',
    'privacy_policy_version',
    'data_retention_category',
    'submitted_at',
    'reviewed_at',
    'reviewed_by',
    'review_notes',
])]
class CarerProfile extends Model
{
    use Auditable;

    public const STATUS_ONBOARDING = 'onboarding';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    public const PHOTO_ID_TYPES = [
        'passport' => 'Passport',
        'uk_driving_licence' => 'UK Driving Licence',
        'biometric_residence_permit' => 'Biometric Residence Permit',
    ];

    public const RIGHT_TO_WORK_STATUSES = [
        'uk_citizen' => 'UK Citizen',
        'settled_status' => 'Settled Status',
        'pre_settled' => 'Pre-settled',
        'work_visa' => 'Work Visa',
        'not_verified' => 'Not Verified',
    ];

    public const VISA_STATUSES = [
        'skilled_worker_visa' => 'Skilled Worker Visa',
        'student_visa' => 'Student Visa',
        'dependent_visa' => 'Dependent Visa',
        'other' => 'Other',
        'na' => 'N/A',
    ];

    public const JOB_TITLES = [
        'carer' => 'Carer',
        'senior_carer' => 'Senior Carer',
        'nurse' => 'Nurse',
        'support_worker' => 'Support Worker',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'agency' => 'Agency',
        'bank' => 'Bank',
    ];

    public const DBS_CHECK_STATUSES = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'expired' => 'Expired',
        'rejected' => 'Rejected',
    ];

    public const YES_NO_OPTIONS = [
        'yes' => 'Yes',
        'no' => 'No',
    ];

    public const OCCUPATIONAL_HEALTH_CLEARANCES = [
        'fit' => 'Fit',
        'pending' => 'Pending',
        'not_fit' => 'Not Fit',
    ];

    public const IMMUNISATION_STATUSES = [
        'up_to_date' => 'Up to date',
        'partial' => 'Partial',
        'not_provided' => 'Not provided',
    ];

    public const SKILLS = [
        'dementia_care' => 'Dementia Care',
        'palliative_care' => 'Palliative Care',
        'mobility_support' => 'Mobility Support',
        'medication_administration' => 'Medication Administration',
        'mental_health_support' => 'Mental Health Support',
    ];

    public const LANGUAGES = [
        'english' => 'English',
        'french' => 'French',
        'spanish' => 'Spanish',
        'other' => 'Other',
    ];

    public const AVAILABILITY_PATTERNS = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'flexible' => 'Flexible',
    ];

    public const SHIFT_PREFERENCES = [
        'day' => 'Day',
        'night' => 'Night',
        'both' => 'Both',
    ];

    public const ACCOUNT_STATUSES = [
        'pending' => 'Pending',
        'active' => 'Active',
        'suspended' => 'Suspended',
    ];

    public const DATA_RETENTION_CATEGORIES = [
        'active_staff' => 'Active Staff',
        'former_staff' => 'Former Staff',
        'applicant' => 'Applicant',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<Home, $this>
     */
    public function assignedHome(): BelongsTo
    {
        return $this->belongsTo(Home::class, 'assigned_home_id');
    }

    /**
     * @return HasMany<CarerTrainingRecord, $this>
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(CarerTrainingRecord::class);
    }

    public function photoIdTypeLabel(): string
    {
        return $this->photo_id_type ? (self::PHOTO_ID_TYPES[$this->photo_id_type] ?? str($this->photo_id_type)->headline()->toString()) : 'Not recorded';
    }

    public function rightToWorkStatusLabel(): string
    {
        return $this->right_to_work_status ? (self::RIGHT_TO_WORK_STATUSES[$this->right_to_work_status] ?? str($this->right_to_work_status)->headline()->toString()) : 'Not recorded';
    }

    public function visaStatusLabel(): string
    {
        return $this->visa_status ? (self::VISA_STATUSES[$this->visa_status] ?? str($this->visa_status)->headline()->toString()) : 'Not recorded';
    }

    public function jobTitleLabel(): string
    {
        return $this->job_title ? (self::JOB_TITLES[$this->job_title] ?? str($this->job_title)->headline()->toString()) : 'Not recorded';
    }

    public function employmentTypeLabel(): string
    {
        return $this->employment_type ? (self::EMPLOYMENT_TYPES[$this->employment_type] ?? str($this->employment_type)->headline()->toString()) : 'Not recorded';
    }

    public function dbsCheckStatusLabel(): string
    {
        return $this->dbs_check_status ? (self::DBS_CHECK_STATUSES[$this->dbs_check_status] ?? str($this->dbs_check_status)->headline()->toString()) : 'Not recorded';
    }

    public function safeguardingTrainingCompletedLabel(): string
    {
        return $this->safeguarding_training_completed ? (self::YES_NO_OPTIONS[$this->safeguarding_training_completed] ?? str($this->safeguarding_training_completed)->headline()->toString()) : 'Not recorded';
    }

    public function occupationalHealthClearanceLabel(): string
    {
        return $this->occupational_health_clearance ? (self::OCCUPATIONAL_HEALTH_CLEARANCES[$this->occupational_health_clearance] ?? str($this->occupational_health_clearance)->headline()->toString()) : 'Not recorded';
    }

    public function immunisationStatusLabel(): string
    {
        return $this->immunisation_status ? (self::IMMUNISATION_STATUSES[$this->immunisation_status] ?? str($this->immunisation_status)->headline()->toString()) : 'Not recorded';
    }

    public function skillsLabels(): array
    {
        return collect($this->skills ?: [])
            ->map(fn (string $skill): string => self::SKILLS[$skill] ?? str($skill)->replace('_', ' ')->headline()->toString())
            ->all();
    }

    public function languageLabels(): array
    {
        return collect($this->languages ?: [])
            ->map(fn (string $language): string => self::LANGUAGES[$language] ?? str($language)->replace('_', ' ')->headline()->toString())
            ->all();
    }

    public function availabilityPatternLabel(): string
    {
        return $this->availability_pattern ? (self::AVAILABILITY_PATTERNS[$this->availability_pattern] ?? str($this->availability_pattern)->headline()->toString()) : 'Not recorded';
    }

    public function shiftPreferenceLabel(): string
    {
        return $this->shift_preference ? (self::SHIFT_PREFERENCES[$this->shift_preference] ?? str($this->shift_preference)->headline()->toString()) : 'Not recorded';
    }

    public function accountStatusLabel(): string
    {
        return $this->account_status ? (self::ACCOUNT_STATUSES[$this->account_status] ?? str($this->account_status)->headline()->toString()) : 'Pending';
    }

    /**
     * @return list<string>
     */
    public function criticalValidationFailures(): array
    {
        $failures = [];

        if (blank($this->assigned_home_id)) {
            $failures[] = 'Assigned home is required.';
        }

        if ($this->dbs_check_status !== 'verified') {
            $failures[] = 'DBS status must be verified.';
        }

        if ($this->dbs_check_status === 'verified' && (blank($this->dbs_certificate_number) || blank($this->dbs_expiry_date))) {
            $failures[] = 'DBS certificate number and expiry date are required.';
        }

        if ($this->safeguarding_training_completed !== 'yes' || blank($this->last_safeguarding_training_date)) {
            $failures[] = 'Safeguarding training must be completed and dated.';
        }

        $records = $this->relationLoaded('trainingRecords')
            ? $this->trainingRecords->keyBy('training_key')
            : $this->trainingRecords()->get()->keyBy('training_key');

        foreach (CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName) {
            $record = $records->get($trainingKey);

            if (! $record || $record->status !== 'completed' || blank($record->expiry_date)) {
                $failures[] = "{$trainingName} training must be completed with an expiry date.";
            }
        }

        if ($this->occupational_health_clearance !== 'fit') {
            $failures[] = 'Occupational health clearance must be Fit.';
        }

        if (! $this->fit_to_work_declaration) {
            $failures[] = 'Fit-to-work declaration must be confirmed.';
        }

        if (! $this->data_processing_consent || ! $this->privacy_policy_accepted) {
            $failures[] = 'Data processing consent and privacy policy acceptance are required.';
        }

        return $failures;
    }

    public function isActivationReady(): bool
    {
        return $this->criticalValidationFailures() === [];
    }

    public function dataRetentionCategoryLabel(): string
    {
        return $this->data_retention_category ? (self::DATA_RETENTION_CATEGORIES[$this->data_retention_category] ?? str($this->data_retention_category)->headline()->toString()) : 'Not recorded';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'start_date' => 'date',
            'dbs_expiry_date' => 'date',
            'last_safeguarding_training_date' => 'date',
            'fit_to_work_declaration' => 'boolean',
            'skills' => 'array',
            'languages' => 'array',
            'max_weekly_hours' => 'integer',
            'mfa_enabled' => 'boolean',
            'data_processing_consent' => 'boolean',
            'data_processing_consented_at' => 'datetime',
            'privacy_policy_accepted' => 'boolean',
            'privacy_policy_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
