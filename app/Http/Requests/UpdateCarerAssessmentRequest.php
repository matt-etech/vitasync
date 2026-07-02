<?php

namespace App\Http\Requests;

use App\Models\CarerProfile;
use App\Models\CarerTrainingRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCarerAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var \App\Models\User|null $carer */
        $carer = $this->route('carer');

        return [
            'legal_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'national_insurance_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-CEGHJ-PR-TW-Z]{2}[0-9]{6}[A-D]$/i'],
            'photo_id_type' => ['nullable', Rule::in(array_keys(CarerProfile::PHOTO_ID_TYPES))],
            'id_document_number' => ['nullable', 'string', 'max:255', 'alpha_num'],
            'id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'right_to_work_status' => ['nullable', Rule::in(array_keys(CarerProfile::RIGHT_TO_WORK_STATUSES))],
            'visa_status' => ['nullable', 'required_unless:right_to_work_status,uk_citizen', Rule::in(array_keys(CarerProfile::VISA_STATUSES))],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}$/i'],
            'contact_phone' => ['nullable', 'string', 'max:50', 'regex:/^(?:\+44\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$|^(?:\+44\s?|0)(?:1\d{3}|2\d|3\d{2})\s?\d{3,4}\s?\d{3,4}$/'],
            'contact_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($carer)],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50', 'regex:/^(?:\+44\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$|^(?:\+44\s?|0)(?:1\d{3}|2\d|3\d{2})\s?\d{3,4}\s?\d{3,4}$/'],
            'job_title' => ['nullable', Rule::in(array_keys(CarerProfile::JOB_TITLES))],
            'employment_type' => ['nullable', Rule::in(array_keys(CarerProfile::EMPLOYMENT_TYPES))],
            'start_date' => ['nullable', 'date'],
            'assigned_home_id' => ['nullable', 'integer', Rule::exists('homes', 'id')->where('status', 'active')],
            'dbs_check_status' => ['nullable', Rule::in(array_keys(CarerProfile::DBS_CHECK_STATUSES))],
            'dbs_certificate_number' => ['nullable', 'required_if:dbs_check_status,verified', 'string', 'max:255'],
            'dbs_expiry_date' => ['nullable', 'required_if:dbs_check_status,verified', 'date', 'after_or_equal:today'],
            'safeguarding_training_completed' => ['nullable', Rule::in(array_keys(CarerProfile::YES_NO_OPTIONS))],
            'last_safeguarding_training_date' => ['nullable', 'required_if:safeguarding_training_completed,yes', 'date', 'before_or_equal:today'],
            'trainings' => ['nullable', 'array'],
            'trainings.*.status' => ['required', Rule::in(array_keys(CarerTrainingRecord::STATUSES))],
            'trainings.*.expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'trainings.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'occupational_health_clearance' => ['nullable', Rule::in(array_keys(CarerProfile::OCCUPATIONAL_HEALTH_CLEARANCES))],
            'immunisation_status' => ['nullable', Rule::in(array_keys(CarerProfile::IMMUNISATION_STATUSES))],
            'fit_to_work_declaration' => ['nullable', 'accepted'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', Rule::in(array_keys(CarerProfile::SKILLS))],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', Rule::in(array_keys(CarerProfile::LANGUAGES))],
            'availability_pattern' => ['nullable', Rule::in(array_keys(CarerProfile::AVAILABILITY_PATTERNS))],
            'max_weekly_hours' => ['nullable', 'integer', 'min:1', 'max:60'],
            'shift_preference' => ['nullable', Rule::in(array_keys(CarerProfile::SHIFT_PREFERENCES))],
            'account_status' => ['nullable', Rule::in(array_keys(CarerProfile::ACCOUNT_STATUSES))],
            'mfa_enabled' => ['nullable', 'boolean'],
            'data_processing_consent' => ['nullable', 'accepted'],
            'privacy_policy_accepted' => ['nullable', 'accepted'],
            'data_retention_category' => ['nullable', Rule::in(array_keys(CarerProfile::DATA_RETENTION_CATEGORIES))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postcode.regex' => 'Enter a valid UK postcode.',
            'contact_phone.regex' => 'Enter a valid UK phone number.',
            'emergency_contact_phone.regex' => 'Enter a valid UK phone number.',
            'contact_email.unique' => 'This email address is already used by another user.',
            'dbs_certificate_number.required_if' => 'Enter the DBS certificate number when DBS is verified.',
            'dbs_expiry_date.required_if' => 'Enter the DBS expiry date when DBS is verified.',
            'last_safeguarding_training_date.required_if' => 'Enter the last safeguarding training date when safeguarding training is completed.',
            'trainings.*.expiry_date.required_if' => 'Enter the expiry date when training is completed.',
            'trainings.*.certificate.mimes' => 'Training certificates must be PDF, JPG, JPEG, or PNG files.',
            'trainings.*.certificate.max' => 'Training certificates must be 10MB or smaller.',
            'fit_to_work_declaration.accepted' => 'Confirm the fit-to-work declaration before saving.',
            'account_status.required' => 'Select an account status.',
            'data_processing_consent.accepted' => 'Consent to data processing is required.',
            'privacy_policy_accepted.accepted' => 'Privacy policy acceptance is required.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $nameParts = preg_split('/\s+/', trim((string) $this->input('legal_name')));

                if (filled($this->input('legal_name')) && count(array_filter($nameParts ?: [])) < 2) {
                    $validator->errors()->add('legal_name', 'The full legal name must include at least two names.');
                }

                if ($this->has('trainings')) {
                    foreach (array_keys(CarerTrainingRecord::MANDATORY_TRAINING) as $trainingKey) {
                        if (! $this->has("trainings.{$trainingKey}.status")) {
                            $validator->errors()->add("trainings.{$trainingKey}.status", 'Select a status for this training.');
                        }

                        if ($this->input("trainings.{$trainingKey}.status") === 'completed' && blank($this->input("trainings.{$trainingKey}.expiry_date"))) {
                            $validator->errors()->add("trainings.{$trainingKey}.expiry_date", 'Enter the expiry date when training is completed.');
                        }
                    }
                }

                if ($this->input('account_status') === 'active') {
                    $this->validateActiveAccountReadiness($validator);
                }
            },
        ];
    }

    private function validateActiveAccountReadiness(Validator $validator): void
    {
        if (blank($this->input('assigned_home_id'))) {
            $validator->errors()->add('account_status', 'Assign a home before making this account active.');
        }

        if ($this->input('dbs_check_status') !== 'verified') {
            $validator->errors()->add('account_status', 'DBS status must be verified before making this account active.');
        }

        if (blank($this->input('dbs_certificate_number')) || blank($this->input('dbs_expiry_date'))) {
            $validator->errors()->add('account_status', 'DBS certificate number and expiry date are required before making this account active.');
        }

        if ($this->input('safeguarding_training_completed') !== 'yes' || blank($this->input('last_safeguarding_training_date'))) {
            $validator->errors()->add('account_status', 'Safeguarding training must be completed and dated before making this account active.');
        }

        foreach (array_keys(CarerTrainingRecord::MANDATORY_TRAINING) as $trainingKey) {
            if ($this->input("trainings.{$trainingKey}.status") !== 'completed' || blank($this->input("trainings.{$trainingKey}.expiry_date"))) {
                $validator->errors()->add('account_status', 'All mandatory training must be completed with expiry dates before making this account active.');
                break;
            }
        }

        if ($this->input('occupational_health_clearance') !== 'fit') {
            $validator->errors()->add('account_status', 'Occupational health clearance must be Fit before making this account active.');
        }

        if (! $this->boolean('fit_to_work_declaration')) {
            $validator->errors()->add('account_status', 'Fit-to-work declaration must be confirmed before making this account active.');
        }

        if (! $this->boolean('data_processing_consent') || ! $this->boolean('privacy_policy_accepted')) {
            $validator->errors()->add('account_status', 'Consent and privacy policy acceptance are required before making this account active.');
        }
    }
}
