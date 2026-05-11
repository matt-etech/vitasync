<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CarerVisitLocationEventRequest extends FormRequest
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
            'event_type' => ['required', 'string', 'in:arrived,departed'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'distance_meters' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
