<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarerVisitTaskRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carer_id' => ['required', 'integer', 'exists:users,id'],
            'task_key' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', Rule::in(['completed', 'pending', 'refused', 'missed'])],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
