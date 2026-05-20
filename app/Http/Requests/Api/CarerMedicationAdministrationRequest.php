<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarerMedicationAdministrationRequest extends FormRequest
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
            'medication_name' => ['nullable', 'string', 'max:255'],
            'dose' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:120'],
            'outcome' => ['required', 'string', Rule::in(['administered', 'refused', 'missed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'administered_at' => ['nullable', 'date'],
        ];
    }
}
