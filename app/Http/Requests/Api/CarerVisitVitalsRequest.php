<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CarerVisitVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carer_id' => ['required', 'integer', 'exists:users,id'],
            'bp_systolic' => ['required', 'integer', 'between:70,250'],
            'bp_diastolic' => ['required', 'integer', 'between:40,150'],
            'pulse' => ['required', 'integer', 'between:30,220'],
            'temperature' => ['required', 'numeric', 'between:30,45'],
            'blood_oxygen' => ['required', 'integer', 'between:50,100'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
