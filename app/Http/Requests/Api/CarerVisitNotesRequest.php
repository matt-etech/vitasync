<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CarerVisitNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'carer_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['required', 'string', 'min:6', 'max:5000'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
