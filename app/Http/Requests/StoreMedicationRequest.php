<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'name' => ['required', 'string', 'max:255'],
            'dose' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', Rule::in(['Oral', 'Topical', 'Inhaled', 'Eye drops', 'Ear drops', 'Injection', 'Patch', 'Other'])],
            'frequency' => ['nullable', 'string', 'max:255'],
            'support_level' => ['required', Rule::in(['Self-administers', 'Prompting', 'Assistance required', 'Administration by carer', 'MAR chart required', 'District nurse support'])],
            'status' => ['required', Rule::in(['active', 'paused', 'stopped'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
