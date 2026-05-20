<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CarerFamilyMessageRequest extends FormRequest
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
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
