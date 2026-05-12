<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiskReviewRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'risk_domain' => ['required', Rule::in(['Falls', 'Pressure ulcer', 'Manual handling', 'Environmental', 'Behaviour', 'Safeguarding', 'Medication', 'Nutrition', 'Other'])],
            'risk_level' => ['required', Rule::in(['None', 'Low', 'Medium', 'High', 'Critical', 'Not assessed'])],
            'hazards' => ['nullable', 'string', 'max:5000'],
            'control_measures' => ['nullable', 'string', 'max:5000'],
            'review_date' => ['required', 'date'],
            'next_review_date' => ['nullable', 'date', 'after_or_equal:review_date'],
            'status' => ['required', Rule::in(['open', 'managed', 'closed'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
