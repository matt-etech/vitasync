<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarerIssueReportRequest extends FormRequest
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
            'carer_id' => ['required', 'integer', 'exists:users,id'],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
            'category' => ['required', 'string', Rule::in([
                'Client not home',
                'Late visit',
                'Medication concern',
                'Safeguarding concern',
                'Emergency escalation',
            ])],
            'severity' => ['required', 'string', Rule::in(['Info', 'Warning', 'Critical'])],
            'notes' => ['required', 'string', 'min:6', 'max:2000'],
            'reported_at' => ['nullable', 'date'],
        ];
    }
}
