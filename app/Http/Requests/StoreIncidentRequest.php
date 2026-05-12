<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
            'category' => ['required', Rule::in(['Fall', 'Medication', 'Behaviour', 'Injury', 'Safeguarding', 'Missing person', 'Property/environment', 'Other'])],
            'severity' => ['required', Rule::in(['Info', 'Low', 'Medium', 'High', 'Critical'])],
            'occurred_at' => ['required', 'date'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'immediate_actions' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['open', 'investigating', 'closed'])],
            'safeguarding_required' => ['nullable', 'boolean'],
        ];
    }
}
