<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationAdministrationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'medication_id' => ['required', 'integer', Rule::exists('medications', 'id')->where('status', 'active')],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
            'outcome' => ['required', Rule::in(['Administered', 'Prompted', 'Refused', 'Not available', 'Withheld', 'Self-administered', 'Error reported'])],
            'administered_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
