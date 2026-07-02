<?php

namespace App\Http\Requests;

use App\Models\FamilyMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $familyMember = $this->route('family_member') ?? $this->route('family-member');

        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'client_ids' => ['nullable', 'array', 'min:1'],
            'client_ids.*' => ['integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('family_members', 'email')->ignore($familyMember)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'access_notes' => ['nullable', 'string', 'max:2000'],
            ...collect(FamilyMember::ACCESS_FIELDS)->mapWithKeys(fn (string $field): array => [$field => ['nullable', 'boolean']])->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'client_ids' => 'assigned clients',
            'client_ids.*' => 'assigned client',
            'is_active' => 'family access active',
            'access_notes' => 'access notes',
        ];
    }
}
