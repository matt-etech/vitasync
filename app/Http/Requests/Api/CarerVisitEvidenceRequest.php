<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarerVisitEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carer_id' => ['required', 'integer', 'exists:users,id'],
            'evidence_type' => ['required', 'string', Rule::in(['photo', 'signature'])],
            'label' => ['required', 'string', 'max:255'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'captured_at' => ['nullable', 'date'],
        ];
    }
}
