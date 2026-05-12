<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSafeguardingCaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'incident_id' => ['nullable', 'integer', 'exists:incidents,id'],
            'concern_type' => ['required', Rule::in(['Neglect', 'Physical abuse', 'Emotional abuse', 'Financial abuse', 'Medication concern', 'Self-neglect', 'Domestic abuse', 'Organisational abuse', 'Other'])],
            'risk_level' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'status' => ['required', Rule::in(['open', 'referred', 'monitoring', 'closed'])],
            'opened_at' => ['required', 'date'],
            'referred_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'summary' => ['required', 'string', 'min:10', 'max:5000'],
            'actions_taken' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
