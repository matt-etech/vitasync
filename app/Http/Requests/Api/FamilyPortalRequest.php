<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class FamilyPortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
        ];
    }
}
