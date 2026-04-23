<?php

namespace App\Http\Requests;

use App\Models\Home;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomeRequest extends FormRequest
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
        /** @var Home|null $home */
        $home = $this->route('home');

        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255', Rule::unique('homes', 'registration_number')->ignore($home)],
            'care_type' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'onboarding'])],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }
}
