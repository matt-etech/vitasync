<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
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
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'care_plan_id' => ['nullable', 'integer', 'exists:care_plans,id'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'missed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $clientId = $this->integer('client_id');
            $carePlanId = $this->integer('care_plan_id');

            if ($clientId <= 0 || $carePlanId <= 0) {
                return;
            }

            $client = Client::find($clientId);

            if ($client === null) {
                return;
            }

            $matchesClient = $client->carePlans()
                ->whereKey($carePlanId)
                ->exists();

            if (! $matchesClient) {
                $validator->errors()->add('care_plan_id', 'The selected care plan must belong to the selected client.');
            }
        });
    }
}
