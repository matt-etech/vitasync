<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'home_id' => ['nullable', 'integer', Rule::exists('homes', 'id')->where('status', 'active')],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('is_active', true)],
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
        ];
    }
}
