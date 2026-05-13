<?php

namespace App\Http\Requests;

use App\Models\FamilyMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:family_members,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'access_notes' => ['nullable', 'string', 'max:2000'],
            ...collect(FamilyMember::ACCESS_FIELDS)->mapWithKeys(fn (string $field): array => [$field => ['nullable', 'boolean']])->all(),
        ];
    }
}
