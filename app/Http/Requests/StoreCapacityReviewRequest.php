<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCapacityReviewRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'decision_type' => ['required', Rule::in(['Care package', 'Medication', 'Finances', 'Accommodation', 'Personal care', 'Medical treatment', 'Contact with others', 'Safeguarding', 'Other'])],
            'capacity_outcome' => ['required', Rule::in(['Has capacity', 'Lacks capacity', 'Fluctuating capacity', 'Unable to assess', 'Needs formal assessment'])],
            'best_interest_status' => ['nullable', Rule::in(['Not required', 'Required', 'Completed', 'Pending', 'Family consulted', 'Advocate/IMCA required'])],
            'advocate_status' => ['nullable', Rule::in(['Not required', 'Family representative', 'Advocate involved', 'IMCA referral needed', 'IMCA involved'])],
            'review_date' => ['required', 'date'],
            'next_review_date' => ['nullable', 'date', 'after_or_equal:review_date'],
            'evidence' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
