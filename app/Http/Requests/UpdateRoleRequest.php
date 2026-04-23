<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
        ];
    }
}
