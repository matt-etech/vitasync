<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsentRecordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'consent_type' => ['required', Rule::in(['Care delivery', 'Medication support', 'Information sharing', 'Photo evidence', 'Family contact', 'External referral', 'Other'])],
            'decision' => ['required', Rule::in(['Consented', 'Declined', 'Withdrawn', 'Best-interest decision', 'Unable to decide'])],
            'given_by' => ['required', Rule::in(['Client', 'Representative', 'Attorney/deputy', 'Best-interest process', 'Not applicable'])],
            'recorded_at' => ['required', 'date'],
            'review_date' => ['nullable', 'date'],
            'evidence' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
