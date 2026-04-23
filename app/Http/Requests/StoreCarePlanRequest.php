<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarePlanRequest extends FormRequest
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
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'title' => ['required', 'string', 'max:255'],
            'plan_type' => ['required', Rule::in(['Initial', 'Review', 'Temporary', 'End of life'])],
            'care_level' => ['nullable', Rule::in(['Low', 'Medium', 'High', 'Complex'])],
            'visit_frequency' => ['nullable', Rule::in(['Daily', 'Multiple daily', 'Weekly', 'As needed', 'Live-in'])],
            'review_frequency' => ['nullable', Rule::in(['Weekly', 'Monthly', 'Quarterly', 'Six monthly', 'Annual'])],
            'start_date' => ['required', 'date'],
            'review_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'care_goals' => ['nullable', 'string', 'max:5000'],
            'personal_care_level' => ['nullable', Rule::in(['Independent', 'Prompting', 'Partial support', 'Full support'])],
            'personal_care_support' => ['nullable', 'string', 'max:5000'],
            'mobility_level' => ['nullable', Rule::in(['Independent', 'Prompting', 'One person assist', 'Two person assist', 'Hoist'])],
            'mobility_support' => ['nullable', 'string', 'max:5000'],
            'nutrition_support_level' => ['nullable', Rule::in(['Independent', 'Prompting', 'Meal preparation', 'Assisted eating', 'Specialist diet'])],
            'nutrition_hydration_support' => ['nullable', 'string', 'max:5000'],
            'medication_support_level' => ['nullable', Rule::in(['None', 'Prompting', 'Administered by staff', 'Monitored', 'Managed by clinician'])],
            'medication_support' => ['nullable', 'string', 'max:5000'],
            'communication_support_level' => ['nullable', Rule::in(['Verbal', 'Non-verbal', 'Hearing support', 'Vision support', 'Interpreter required'])],
            'communication_support' => ['nullable', 'string', 'max:5000'],
            'risk_level' => ['nullable', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'risk_management' => ['nullable', 'string', 'max:5000'],
            'preferences_routines' => ['nullable', 'string', 'max:5000'],
            'escalation_instructions' => ['nullable', 'string', 'max:5000'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ];
    }
}
